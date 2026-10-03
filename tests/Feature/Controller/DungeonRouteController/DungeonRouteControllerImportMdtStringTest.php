<?php

namespace Tests\Feature\Controller\DungeonRouteController;

use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteDraftSource;
use App\Models\KillZone\KillZone;
use App\Models\Laratrust\Role;
use App\Models\PublishedState;
use App\Models\Team;
use App\Models\TeamUser;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\App\Service\MDT\MDTImportStringServiceTestBase;

#[Group('Controller')]
#[Group('DungeonRoute')]
#[Group('UsesLua')]
#[Group('DungeonRouteControllerImportMdtString')]
final class DungeonRouteControllerImportMdtStringTest extends MDTImportStringServiceTestBase
{
    /**
     * Every model created by a test, torn down in order.
     *
     * @var array<int, Model>
     */
    private array $cleanup = [];

    #[Test]
    public function importMdtString_givenGuest_returnsUnauthorized(): void
    {
        try {
            // Arrange
            $original = $this->createOriginal($this->createUser());

            // Act
            $response = $this->postJson($this->importUrl($original), ['import_string' => 'irrelevant']);

            // Assert
            $response->assertUnauthorized();
            $this->assertNull($original->upgradeDraft()->first());
        } finally {
            $this->tearDownCleanup();
        }
    }

    #[Test]
    public function importMdtString_givenNonOwner_returnsForbidden(): void
    {
        try {
            // Arrange
            $original = $this->createOriginal($this->createUser());
            $stranger = $this->createUser();

            // Act
            $response = $this->actingAs($stranger)->postJson($this->importUrl($original), ['import_string' => 'irrelevant']);

            // Assert
            $response->assertForbidden();
            $this->assertNull($original->upgradeDraft()->first());
        } finally {
            $this->tearDownCleanup();
        }
    }

    #[Test]
    public function importMdtString_givenOwner_returnsRedirectToDraftEditPage(): void
    {
        try {
            // Arrange
            [$source, $mdtString] = $this->createSourceRouteAndString();
            $owner                = $this->createUser();
            $original             = $this->createOriginal($owner, $source);

            // Act
            $response = $this->actingAs($owner)->postJson($this->importUrl($original), ['import_string' => $mdtString]);

            // Assert
            $response->assertOk();
            $draft = $original->upgradeDraft()->first();
            $this->assertNotNull($draft);
            $this->assertSame(DungeonRouteDraftSource::MdtImport, $draft->draft_source);
            $response->assertJson([
                'redirect_url' => route('dungeonroute.edit', [
                    'dungeon'      => $draft->dungeon,
                    'dungeonroute' => $draft,
                    'title'        => $draft->getTitleSlug(),
                ]),
            ]);
            $response->assertSessionHas('status', __('controller.dungeonroute.flash.mdt_import_draft_created'));
        } finally {
            $this->tearDownCleanup();
        }
    }

    #[Test]
    public function importMdtString_givenTeamCollaborator_returnsRedirectToDraftEditPage(): void
    {
        try {
            // Arrange
            [$source, $mdtString] = $this->createSourceRouteAndString();
            $collaborator         = $this->createUser();
            $team                 = $this->createTeamWith($collaborator, TeamUser::ROLE_COLLABORATOR);
            $original             = $this->createOriginal($this->createUser(), $source, [
                'team_id'            => $team->id,
                'published_state_id' => PublishedState::ALL[PublishedState::TEAM],
            ]);

            // Act
            $response = $this->actingAs($collaborator)->postJson($this->importUrl($original), ['import_string' => $mdtString]);

            // Assert
            $response->assertOk();
            $draft = $original->upgradeDraft()->first();
            $this->assertNotNull($draft);
            $this->assertSame($team->id, $draft->team_id);
            $this->assertSame($original->author_id, $draft->author_id, 'The draft stays the original author\'s route');
        } finally {
            $this->tearDownCleanup();
        }
    }

    #[Test]
    public function importMdtString_givenPlainTeamMember_returnsForbidden(): void
    {
        try {
            // Arrange
            $member   = $this->createUser();
            $team     = $this->createTeamWith($member, TeamUser::ROLE_MEMBER);
            $original = $this->createOriginal($this->createUser(), null, [
                'team_id'            => $team->id,
                'published_state_id' => PublishedState::ALL[PublishedState::TEAM],
            ]);

            // Act
            $response = $this->actingAs($member)->postJson($this->importUrl($original), ['import_string' => 'irrelevant']);

            // Assert
            $response->assertForbidden();
        } finally {
            $this->tearDownCleanup();
        }
    }

    #[Test]
    public function importMdtString_givenMissingString_returnsValidationError(): void
    {
        try {
            // Arrange
            $owner    = $this->createUser();
            $original = $this->createOriginal($owner);

            // Act
            $response = $this->actingAs($owner)->postJson($this->importUrl($original), []);

            // Assert
            $response->assertUnprocessable();
            $response->assertJsonValidationErrors('import_string');
        } finally {
            $this->tearDownCleanup();
        }
    }

    #[Test]
    public function importMdtString_givenStringForOtherDungeon_returnsBadRequest(): void
    {
        try {
            // Arrange
            [$source, $mdtString] = $this->createSourceRouteAndString();
            $owner                = $this->createUser();
            $otherDungeon         = Dungeon::query()
                ->where('id', '!=', $source->dungeon_id)
                ->whereHas('mappingVersions')
                ->firstOrFail();
            $original = $this->createOriginal($owner, null, [
                'dungeon_id'         => $otherDungeon->id,
                'mapping_version_id' => $otherDungeon->getCurrentMappingVersion()->id,
            ]);

            // Act
            $response = $this->actingAs($owner)->postJson($this->importUrl($original), ['import_string' => $mdtString]);

            // Assert
            $response->assertBadRequest();
            $this->assertStringContainsString(__($otherDungeon->name), (string)$response->json('message'));
            $this->assertNull($original->upgradeDraft()->first());
        } finally {
            $this->tearDownCleanup();
        }
    }

    #[Test]
    public function importMdtString_givenPendingDraftWithoutConfirmation_returnsConflict(): void
    {
        try {
            // Arrange
            [$source, $mdtString] = $this->createSourceRouteAndString();
            $owner                = $this->createUser();
            $original             = $this->createOriginal($owner, $source);
            $existingDraft        = $this->createExistingDraft($original);

            // Act
            $response = $this->actingAs($owner)->postJson($this->importUrl($original), ['import_string' => $mdtString]);

            // Assert
            $response->assertConflict();
            $this->assertNotNull(DungeonRoute::find($existingDraft->id));
        } finally {
            $this->tearDownCleanup();
        }
    }

    #[Test]
    public function importMdtString_givenPendingDraftWithConfirmation_replacesDraft(): void
    {
        try {
            // Arrange
            [$source, $mdtString] = $this->createSourceRouteAndString();
            $owner                = $this->createUser();
            $original             = $this->createOriginal($owner, $source);
            $existingDraft        = $this->createExistingDraft($original);

            // Act
            $response = $this->actingAs($owner)->postJson($this->importUrl($original), [
                'import_string'             => $mdtString,
                'discard_existing_draft_id' => $existingDraft->id,
            ]);

            // Assert
            $response->assertOk();
            $this->assertNull(DungeonRoute::find($existingDraft->id));
            $this->assertSame(DungeonRouteDraftSource::MdtImport, $original->upgradeDraft()->first()?->draft_source);
        } finally {
            $this->tearDownCleanup();
        }
    }

    #[Test]
    public function importMdtString_givenConfirmationForReplacedDraft_returnsConflictAndKeepsCurrentDraft(): void
    {
        try {
            // Arrange
            [$source, $mdtString] = $this->createSourceRouteAndString();
            $owner                = $this->createUser();
            $original             = $this->createOriginal($owner, $source);
            $draftA               = $this->createExistingDraft($original);
            $payload              = [
                'import_string'             => $mdtString,
                'discard_existing_draft_id' => $draftA->id,
            ];
            $this->actingAs($owner)->postJson($this->importUrl($original), $payload)->assertOk();
            $draftB = $original->upgradeDraft()->firstOrFail();
            array_unshift($this->cleanup, $draftB);
            $maxRouteId = DungeonRoute::query()->max('id');

            // Act
            $response = $this->actingAs($owner)->postJson($this->importUrl($original), $payload);

            // Assert
            $response->assertConflict();
            $this->assertSame(__('services.dungeonroute.upgrade_draft.mdt_import_draft_changed'), $response->json('message'));
            $this->assertSame($draftB->id, $original->upgradeDraft()->first()?->id, 'The replacement the first request made must survive');
            $this->assertFalse(DungeonRoute::query()->where('id', '>', $maxRouteId)->exists(), 'The refused import leaves no route behind');
        } finally {
            $this->tearDownCleanup();
        }
    }

    #[Test]
    public function importMdtString_givenNonIntegerDraftId_returnsUnprocessable(): void
    {
        try {
            // Arrange
            $owner    = $this->createUser();
            $original = $this->createOriginal($owner);

            // Act
            $response = $this->actingAs($owner)->postJson($this->importUrl($original), [
                'import_string'             => 'irrelevant',
                'discard_existing_draft_id' => 'not-an-id',
            ]);

            // Assert
            $response->assertUnprocessable();
            $response->assertJsonValidationErrors('discard_existing_draft_id');
        } finally {
            $this->tearDownCleanup();
        }
    }

    #[Test]
    public function importMdtString_givenAuthorAtRouteLimit_returnsForbiddenWithLimitMessage(): void
    {
        try {
            // Arrange
            $owner    = $this->createUser();
            $original = $this->createOriginal($owner);
            Config::set('keystoneguru.registered_user_dungeonroute_limit', 1);

            // Act
            $response = $this->actingAs($owner)->postJson($this->importUrl($original), ['import_string' => 'irrelevant']);

            // Assert
            $response->assertForbidden();
            $this->assertSame(
                sprintf(__('view_dungeonroute.limitreached.limit_reached_description'), 1),
                $response->json('message'),
            );
        } finally {
            $this->tearDownCleanup();
        }
    }

    #[Test]
    public function importMdtString_givenAuthorAtRouteLimitReplacingPendingDraft_replacesDraft(): void
    {
        try {
            // Arrange
            [$source, $mdtString] = $this->createSourceRouteAndString();
            $owner                = $this->createUser();
            $original             = $this->createOriginal($owner, $source);
            $existingDraft        = $this->createExistingDraft($original);
            // The original and its pending draft use up the limit; replacing the draft does not add a route
            Config::set('keystoneguru.registered_user_dungeonroute_limit', 2);

            // Act
            $response = $this->actingAs($owner)->postJson($this->importUrl($original), [
                'import_string'             => $mdtString,
                'discard_existing_draft_id' => $existingDraft->id,
            ]);

            // Assert
            $response->assertOk();
            $this->assertNull(DungeonRoute::find($existingDraft->id));
            $this->assertSame(DungeonRouteDraftSource::MdtImport, $original->upgradeDraft()->first()?->draft_source);
            $this->assertSame(2, $owner->dungeonRoutes()->count(), 'The author still has as many routes as before');
        } finally {
            $this->tearDownCleanup();
        }
    }

    #[Test]
    public function importMdtString_givenAuthorAtRouteLimitWithDraftIdOfOtherRoute_returnsForbiddenWithLimitMessage(): void
    {
        try {
            // Arrange
            $owner         = $this->createUser();
            $original      = $this->createOriginal($owner);
            $otherOriginal = $this->createOriginal($owner);
            $otherDraft    = $this->createExistingDraft($otherOriginal);
            Config::set('keystoneguru.registered_user_dungeonroute_limit', 3);

            // Act
            $response = $this->actingAs($owner)->postJson($this->importUrl($original), [
                'import_string'             => 'irrelevant',
                'discard_existing_draft_id' => $otherDraft->id,
            ]);

            // Assert
            $response->assertForbidden();
            $this->assertSame(
                sprintf(__('view_dungeonroute.limitreached.limit_reached_description'), 3),
                $response->json('message'),
            );
            $this->assertNotNull(DungeonRoute::find($otherDraft->id), 'Another route\'s draft is never touched');
        } finally {
            $this->tearDownCleanup();
        }
    }

    #[Test]
    public function applyUpgrade_givenMdtImportDraft_returnsMdtImportAppliedStatus(): void
    {
        try {
            // Arrange
            $owner    = $this->createUser();
            $original = $this->createOriginal($owner);
            $draft    = $this->createExistingDraft($original, DungeonRouteDraftSource::MdtImport);

            // Act
            $response = $this->actingAs($owner)->postJson(route('dungeonroute.upgrade.apply', [
                'dungeon'      => $draft->dungeon,
                'dungeonroute' => $draft,
                'title'        => $draft->getTitleSlug(),
            ]));

            // Assert
            $response->assertOk();
            $response->assertJson(['status' => __('controller.dungeonroute.flash.mdt_import_applied')]);
        } finally {
            $this->tearDownCleanup();
        }
    }

    #[Test]
    public function discardUpgrade_givenMdtImportDraft_returnsMdtImportDiscardedStatus(): void
    {
        try {
            // Arrange
            $owner    = $this->createUser();
            $original = $this->createOriginal($owner);
            $draft    = $this->createExistingDraft($original, DungeonRouteDraftSource::MdtImport);

            // Act
            $response = $this->actingAs($owner)->postJson(route('dungeonroute.upgrade.discard', [
                'dungeon'      => $draft->dungeon,
                'dungeonroute' => $draft,
                'title'        => $draft->getTitleSlug(),
            ]));

            // Assert
            $response->assertOk();
            $response->assertJson(['status' => __('controller.dungeonroute.flash.mdt_import_discarded')]);
        } finally {
            $this->tearDownCleanup();
        }
    }

    #[Test]
    public function edit_givenOwnedRoute_showsImportMdtStringAction(): void
    {
        try {
            // Arrange
            $owner    = $this->createUser();
            $original = $this->createOriginal($owner);

            // Act
            $response = $this->actingAs($owner)->followingRedirects()->get(route('dungeonroute.edit', [
                'dungeon'      => $original->dungeon,
                'dungeonroute' => $original,
                'title'        => $original->getTitleSlug(),
            ]));

            // Assert
            $response->assertOk();
            $response->assertSee('mdt_import_overwrite_modal', false);
            // The inline code options carry the url JSON encoded
            $response->assertSee(trim((string)json_encode($this->importUrl($original)), '"'), false);
        } finally {
            $this->tearDownCleanup();
        }
    }

    #[Test]
    public function edit_givenMdtImportDraftWithFlashedWarnings_showsWarningsAndNoImportAction(): void
    {
        try {
            // Arrange
            $owner    = $this->createUser();
            $original = $this->createOriginal($owner);
            $draft    = $this->createExistingDraft($original, DungeonRouteDraftSource::MdtImport);

            // Act
            $response = $this->actingAs($owner)
                ->withSession(['mdt_import_warnings' => ['Unable to find an enemy for this pull']])
                ->followingRedirects()
                ->get(route('dungeonroute.edit', [
                    'dungeon'      => $draft->dungeon,
                    'dungeonroute' => $draft,
                    'title'        => $draft->getTitleSlug(),
                ]));

            // Assert
            $response->assertOk();
            $response->assertSee('mdt_import_warnings_modal', false);
            $response->assertSee('Unable to find an enemy for this pull');
            $response->assertDontSee('mdt_import_overwrite_modal', false);
        } finally {
            $this->tearDownCleanup();
        }
    }

    private function importUrl(DungeonRoute $original): string
    {
        return route('dungeonroute.upgrade.mdtimport', [
            'dungeon'      => $original->dungeon,
            'dungeonroute' => $original,
            'title'        => $original->getTitleSlug(),
        ]);
    }

    private function createUser(): User
    {
        $user = User::factory()->create();
        $user->addRole(Role::firstWhere('name', Role::ROLE_USER));
        $this->cleanup[] = $user;

        return $user;
    }

    private function createTeamWith(User $user, string $role): Team
    {
        $team = Team::create([
            'name'         => sprintf('Import test %s', uniqid()),
            'public_key'   => Team::generateRandomPublicKey(),
            'invite_code'  => Team::generateRandomPublicKey(12, 'invite_code'),
            'description'  => 'Created by DungeonRouteControllerImportMdtStringTest',
            'icon_file_id' => -1,
            'default_role' => TeamUser::ROLE_MEMBER,
        ]);
        $this->cleanup[] = $team;

        TeamUser::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'role'    => $role,
        ]);

        return $team;
    }

    /**
     * @return array{0: DungeonRoute, 1: string}
     */
    private function createSourceRouteAndString(): array
    {
        $source = $this->getMDTCompatibleDungeonRouteWithSafeEnemies(2, ['expires_at' => null]);
        array_unshift($this->cleanup, $source);

        $enemies = $this->getSafeMdtEnemies($source, 2);
        foreach ([1, 2] as $index) {
            KillZone::factory()->withEnemies($enemies->get($index - 1))->create([
                'dungeon_route_id' => $source->id,
                'index'            => $index,
                'description'      => null,
            ]);
        }

        return [$source, $this->exportDungeonRouteToString($source)];
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function createOriginal(User $author, ?DungeonRoute $source = null, array $attributes = []): DungeonRoute
    {
        $original = DungeonRoute::factory()->create(array_merge(
            [
                'author_id'          => $author->id,
                'expires_at'         => null,
                'published_state_id' => PublishedState::ALL[PublishedState::UNPUBLISHED],
            ],
            $source === null ? [] : [
                'dungeon_id'         => $source->dungeon_id,
                'mapping_version_id' => $source->mapping_version_id,
            ],
            $attributes,
        ));
        array_unshift($this->cleanup, $original);

        return $original;
    }

    private function createExistingDraft(
        DungeonRoute            $original,
        DungeonRouteDraftSource $draftSource = DungeonRouteDraftSource::MappingUpgrade,
    ): DungeonRoute {
        $draft = DungeonRoute::factory()->create([
            'author_id'                   => $original->author_id,
            'dungeon_id'                  => $original->dungeon_id,
            'mapping_version_id'          => $original->mapping_version_id,
            'upgrade_of_dungeon_route_id' => $original->id,
            'draft_source'                => $draftSource,
            'expires_at'                  => null,
            'published_state_id'          => PublishedState::ALL[PublishedState::UNPUBLISHED],
        ]);
        array_unshift($this->cleanup, $draft);

        return $draft;
    }

    private function tearDownCleanup(): void
    {
        foreach ($this->cleanup as $model) {
            if ($model instanceof DungeonRoute) {
                // Also covers the drafts the endpoint created
                DungeonRoute::query()->where('upgrade_of_dungeon_route_id', $model->id)->get()->each->delete();
            }

            $model->fresh()?->delete();
        }

        $this->cleanup = [];
    }
}
