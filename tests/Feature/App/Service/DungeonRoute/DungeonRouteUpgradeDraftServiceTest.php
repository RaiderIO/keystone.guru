<?php

namespace Tests\Feature\App\Service\DungeonRoute;

use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteFavorite;
use App\Models\DungeonRoute\DungeonRoutePlayerSpecialization;
use App\Models\DungeonRoute\DungeonRouteRating;
use App\Models\Enemy;
use App\Models\KillZone\KillZone;
use App\Models\KillZone\KillZoneEnemy;
use App\Models\MapIcon;
use App\Models\MapIconType;
use App\Models\Mapping\MappingVersion;
use App\Models\PublishedState;
use App\Models\Tags\Tag;
use App\Models\Tags\TagCategory;
use App\Models\Team;
use App\Models\User;
use App\Service\DungeonRoute\DungeonRouteServiceInterface;
use App\Service\DungeonRoute\DungeonRouteUpgradeDraftService;
use App\Service\DungeonRoute\Exceptions\UpgradeDraftException;
use App\Service\DungeonRoute\Exceptions\UpgradeDraftGoneException;
use App\Service\DungeonRoute\Logging\DungeonRouteUpgradeDraftServiceLoggingInterface;
use App\Service\DungeonRoute\ThumbnailServiceInterface;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;

#[Group('DungeonRoute')]
class DungeonRouteUpgradeDraftServiceTest extends DungeonRouteSaveServiceTestCase
{
    /**
     * Every model created by a test, torn down newest first.
     *
     * @var array<int, Model>
     */
    private array $cleanup = [];

    private function buildUpgradeDraftService(?ThumbnailServiceInterface $thumbnailService = null): DungeonRouteUpgradeDraftService
    {
        return new DungeonRouteUpgradeDraftService(
            app(DungeonRouteServiceInterface::class),
            $thumbnailService ?? $this->thumbnailServiceAllowingRefresh(),
            $this->createMockPublic(DungeonRouteUpgradeDraftServiceLoggingInterface::class),
        );
    }

    /**
     * Creates a route on a mapping version that is no longer the dungeon's current one, which is the
     * state that offers the author the Upgrade button.
     *
     * @param  array<string, mixed>                                                     $attributes
     * @return array{0: DungeonRoute, 1: MappingVersion, 2: MappingVersion, 3: Dungeon}
     */
    private function createOutdatedRoute(array $attributes = []): array
    {
        $dungeon        = $this->getDungeonWithNonFacadeFloor();
        $mappingVersion = $dungeon->getCurrentMappingVersion();

        // Make the route's mapping version outdated by creating a newer one for the same dungeon
        $newMappingVersion = $this->createNewerMappingVersion($dungeon, $mappingVersion);
        $this->cleanup[]   = $newMappingVersion;

        $route = DungeonRoute::factory()->create(array_merge([
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $mappingVersion->id,
            'expires_at'         => null,
            'published_state_id' => PublishedState::ALL[PublishedState::WORLD],
        ], $attributes));
        array_unshift($this->cleanup, $route);

        return [$route, $mappingVersion, $newMappingVersion, $dungeon];
    }

    /**
     * Satisfies the required-enemies invariant Apply enforces against a published original (mirroring
     * DungeonRoutePolicy::publish()) by killing every required enemy on the draft's mapping version.
     * Most of these fixtures don't care about that invariant - they exercise something else about
     * Apply - so this exists to keep them decoupled from it rather than each hand-rolling the kill.
     */
    private function killRequiredEnemiesOn(DungeonRoute $draft, ?KillZone $killZone = null): void
    {
        $killZone ??= KillZone::create([
            'dungeon_route_id' => $draft->id,
            'floor_id'         => null,
            'color'            => '#00ff00',
            'index'            => 1,
        ]);

        $requiredEnemies = Enemy::query()
            ->where('mapping_version_id', $draft->mapping_version_id)
            ->where('required', true)
            ->get();

        foreach ($requiredEnemies as $requiredEnemy) {
            KillZoneEnemy::create([
                'kill_zone_id' => $killZone->id,
                'enemy_id'     => $requiredEnemy->id,
            ]);
        }
    }

    private function tearDownCleanup(): void
    {
        foreach ($this->cleanup as $model) {
            $model->refresh();
            $model->delete();
        }
        $this->cleanup = [];
    }

    // ------------------------------------------------------------------ findOrCreateDraft

    #[Test]
    public function findOrCreateDraft_givenOutdatedRoute_createsDraftLinkedToOriginal(): void
    {
        try {
            // Arrange
            [$original] = $this->createOutdatedRoute();

            // Act
            $draft = $this->buildUpgradeDraftService()->findOrCreateDraft($original);

            // Assert
            $this->assertNotSame($original->id, $draft->id);
            $this->assertSame($original->id, $draft->upgrade_of_dungeon_route_id);
            $this->assertTrue($draft->is_upgrade_draft);
            $this->assertNotSame($original->public_key, $draft->public_key);
            $this->assertNull($draft->clone_of, 'A draft is not a clone - clone_of must stay null');
            $this->assertSame($original->title, $draft->title, 'A draft keeps the original title, no clone prefix');
            $this->assertSame(
                PublishedState::ALL[PublishedState::UNPUBLISHED],
                $draft->published_state_id,
                'A draft may never be published on its own',
            );
        } finally {
            $this->tearDownCleanup();
        }
    }

    #[Test]
    public function findOrCreateDraft_givenOutdatedRoute_upgradesDraftMappingVersionAndLeavesOriginalUntouched(): void
    {
        try {
            // Arrange
            [$original, $mappingVersion, $newMappingVersion] = $this->createOutdatedRoute();
            $originalId                                      = $original->id;

            // Act
            $draft = $this->buildUpgradeDraftService()->findOrCreateDraft($original);

            // Assert - re-query: cloneRelationsInto() mutates the source's loaded relation instances
            $this->assertSame($newMappingVersion->id, $draft->mapping_version_id, 'The draft is upgraded');
            $this->assertSame(
                $mappingVersion->id,
                DungeonRoute::findOrFail($originalId)->mapping_version_id,
                'The original must keep serving its old mapping version',
            );
        } finally {
            $this->tearDownCleanup();
        }
    }

    #[Test]
    public function findOrCreateDraft_givenExistingDraft_returnsExistingDraft(): void
    {
        try {
            // Arrange
            [$original] = $this->createOutdatedRoute();
            $service    = $this->buildUpgradeDraftService();
            $firstDraft = $service->findOrCreateDraft($original);

            // Act
            $secondDraft = $service->findOrCreateDraft($original->refresh());

            // Assert
            $this->assertSame($firstDraft->id, $secondDraft->id);
            $this->assertSame(1, DungeonRoute::query()->where('upgrade_of_dungeon_route_id', $original->id)->count());
        } finally {
            $this->tearDownCleanup();
        }
    }

    #[Test]
    public function findOrCreateDraft_givenRouteWithTeam_preservesTeamIdOnDraft(): void
    {
        $team = null;

        try {
            // Arrange
            $team = Team::create([
                'public_key'  => Team::generateRandomPublicKey(),
                'name'        => 'Upgrade draft service team',
                'description' => 'Upgrade draft service team',
            ]);
            [$original] = $this->createOutdatedRoute(['team_id' => $team->id]);

            // Act
            $draft = $this->buildUpgradeDraftService()->findOrCreateDraft($original);

            // Assert
            $this->assertSame($team->id, $draft->team_id, 'The team must be able to work on the draft');
        } finally {
            $this->tearDownCleanup();
            $team?->delete();
        }
    }

    #[Test]
    public function findOrCreateDraft_givenRouteWithTeamMapIcons_doesNotCopyTeamMapIconsIntoDraft(): void
    {
        $team        = null;
        $teamMapIcon = null;

        try {
            // Arrange
            $team = Team::create([
                'public_key'  => Team::generateRandomPublicKey(),
                'name'        => 'Upgrade draft icon team',
                'description' => 'Upgrade draft icon team',
            ]);
            [$original, , , $dungeon] = $this->createOutdatedRoute(['team_id' => $team->id]);
            $floor                    = $dungeon->floors()->where('facade', 0)->firstOrFail();

            MapIcon::factory()->create([
                'dungeon_route_id'   => $original->id,
                'mapping_version_id' => null,
                'floor_id'           => $floor->id,
                'team_id'            => null,
                'map_icon_type_id'   => MapIconType::ALL[MapIconType::MAP_ICON_TYPE_COMMENT],
            ]);
            $teamMapIcon = MapIcon::factory()->create([
                'dungeon_route_id'   => null,
                'mapping_version_id' => null,
                'floor_id'           => $floor->id,
                'team_id'            => $team->id,
                'map_icon_type_id'   => MapIconType::ALL[MapIconType::MAP_ICON_TYPE_COMMENT],
            ]);

            // Act
            $draft = $this->buildUpgradeDraftService()->findOrCreateDraft($original);

            // Assert
            $this->assertSame(
                1,
                MapIcon::query()->where('dungeon_route_id', $draft->id)->count(),
                'Only the route\'s own map icon may be copied into the draft',
            );
            $this->assertNotNull(MapIcon::find($teamMapIcon->id), 'The team wide icon must be left alone');
            $this->assertNull(MapIcon::findOrFail($teamMapIcon->id)->dungeon_route_id);
        } finally {
            $this->tearDownCleanup();
            $teamMapIcon?->refresh()->delete();
            $team?->delete();
        }
    }

    #[Test]
    public function findOrCreateDraft_givenRouteWithSpecializations_copiesPlayerSpecializations(): void
    {
        try {
            // Arrange
            [$original] = $this->createOutdatedRoute();
            DungeonRoutePlayerSpecialization::create([
                'dungeon_route_id'                  => $original->id,
                'character_class_specialization_id' => 1,
            ]);

            // Act
            $draft = $this->buildUpgradeDraftService()->findOrCreateDraft($original);

            // Assert
            $this->assertSame(
                1,
                DungeonRoutePlayerSpecialization::query()->where('dungeon_route_id', $draft->id)->count(),
            );
            $this->assertSame(
                1,
                DungeonRoutePlayerSpecialization::query()->where('dungeon_route_id', $original->id)->count(),
                'The original keeps its own specializations',
            );
        } finally {
            $this->tearDownCleanup();
        }
    }

    #[Test]
    public function findOrCreateDraft_givenDraft_throwsException(): void
    {
        try {
            // Arrange
            [$original] = $this->createOutdatedRoute();
            $draft      = $this->buildUpgradeDraftService()->findOrCreateDraft($original);

            // Assert
            $this->expectException(UpgradeDraftException::class);

            // Act
            $this->buildUpgradeDraftService()->findOrCreateDraft($draft);
        } finally {
            $this->tearDownCleanup();
        }
    }

    #[Test]
    public function findOrCreateDraft_givenSandboxRoute_throwsException(): void
    {
        try {
            // Arrange
            [$original] = $this->createOutdatedRoute(['expires_at' => now()->addHours(2)]);

            // Assert
            $this->expectException(UpgradeDraftException::class);

            // Act
            $this->buildUpgradeDraftService()->findOrCreateDraft($original);
        } finally {
            $this->tearDownCleanup();
        }
    }

    // ------------------------------------------------------------------ apply

    #[Test]
    public function apply_givenDraft_preservesOriginalIdAndPublicKeyAndPublishedState(): void
    {
        try {
            // Arrange
            [$original, , $newMappingVersion] = $this->createOutdatedRoute();
            $originalId                       = $original->id;
            $originalPublicKey                = $original->public_key;
            $originalAuthorId                 = $original->author_id;

            $service = $this->buildUpgradeDraftService();
            $draft   = $service->findOrCreateDraft($original);
            $draft->update(['title' => 'Repaired in the draft']);
            $this->killRequiredEnemiesOn($draft);

            // Act
            $applied = $service->apply($draft);

            // Assert
            $this->assertSame($originalId, $applied->id);
            $this->assertSame($originalPublicKey, $applied->public_key);
            $this->assertSame($originalAuthorId, $applied->author_id);
            $this->assertSame(
                PublishedState::ALL[PublishedState::WORLD],
                $applied->published_state_id,
                'The original\'s published state must survive Apply',
            );
            $this->assertSame($newMappingVersion->id, $applied->mapping_version_id);
            $this->assertSame('Repaired in the draft', $applied->title, 'Route settings are applied too');
        } finally {
            $this->tearDownCleanup();
        }
    }

    #[Test]
    public function apply_givenDraft_copiesKillZonesOntoOriginal(): void
    {
        try {
            // Arrange
            [$original] = $this->createOutdatedRoute();
            $service    = $this->buildUpgradeDraftService();
            $draft      = $service->findOrCreateDraft($original);

            $killZone = KillZone::create([
                'dungeon_route_id' => $draft->id,
                'floor_id'         => null,
                'color'            => '#00ff00',
                'index'            => 1,
            ]);

            // The original is published, so Apply enforces the required-enemies invariant - kill every
            // required enemy on the draft's mapping version so this test still exercises what it is
            // actually about (kill-zone copying), not the guard added in apply_givenDraftMissingRequired…
            $this->killRequiredEnemiesOn($draft, $killZone);

            // Act
            $applied = $service->apply($draft);

            // Assert
            $this->assertSame(1, KillZone::query()->where('dungeon_route_id', $applied->id)->count());
            $this->assertSame(0, KillZone::query()->where('dungeon_route_id', $draft->id)->count());
        } finally {
            $this->tearDownCleanup();
        }
    }

    #[Test]
    public function apply_givenDraft_preservesOriginalRatingsFavoritesTagsAndViews(): void
    {
        $rater = null;

        try {
            // Arrange
            [$original] = $this->createOutdatedRoute();
            $rater      = User::factory()->create();

            $tagCategory = TagCategory::firstWhere('name', TagCategory::DUNGEON_ROUTE_PERSONAL);
            $tag         = Tag::create([
                'tag_category_id' => $tagCategory->id,
                'model_id'        => $original->id,
                'model_class'     => DungeonRoute::class,
                'user_id'         => $rater->id,
                'name'            => 'apply-keeps-tags',
                'color'           => '#ff0000',
            ]);
            $rating = DungeonRouteRating::create([
                'dungeon_route_id' => $original->id,
                'user_id'          => $rater->id,
                'rating'           => 5,
            ]);
            $favorite = DungeonRouteFavorite::create([
                'dungeon_route_id' => $original->id,
                'user_id'          => $rater->id,
            ]);
            DungeonRoute::query()->whereKey($original->id)->update(['views' => 1234]);

            $service = $this->buildUpgradeDraftService();
            $draft   = $service->findOrCreateDraft($original->refresh());
            $this->killRequiredEnemiesOn($draft);

            // Act
            $applied = $service->apply($draft);

            // Assert
            $this->assertNotNull(Tag::find($tag->id), 'Apply must not wipe the original\'s tags');
            $this->assertNotNull(DungeonRouteRating::find($rating->id));
            $this->assertNotNull(DungeonRouteFavorite::find($favorite->id));
            $this->assertSame(1234, $applied->views, 'Views belong to the original, not the draft');
        } finally {
            $this->tearDownCleanup();
            $rater?->delete();
        }
    }

    #[Test]
    public function apply_givenDraft_deletesDraftAndItsRelations(): void
    {
        try {
            // Arrange
            [$original] = $this->createOutdatedRoute();
            $service    = $this->buildUpgradeDraftService();
            $draft      = $service->findOrCreateDraft($original);
            $draftId    = $draft->id;

            $killZone = KillZone::create([
                'dungeon_route_id' => $draftId,
                'floor_id'         => null,
                'color'            => '#00ff00',
                'index'            => 1,
            ]);
            $this->killRequiredEnemiesOn($draft, $killZone);

            // Act
            $service->apply($draft);

            // Assert - count by dungeon_route_id, not by inspecting the (now mutated) $draft instance
            $this->assertNull(DungeonRoute::find($draftId));
            $this->assertSame(0, KillZone::query()->where('dungeon_route_id', $draftId)->count());
        } finally {
            $this->tearDownCleanup();
        }
    }

    #[Test]
    public function apply_givenDraftAppliedTwice_throwsException(): void
    {
        try {
            // Arrange
            [$original] = $this->createOutdatedRoute();
            $service    = $this->buildUpgradeDraftService();
            $draft      = $service->findOrCreateDraft($original);
            $this->killRequiredEnemiesOn($draft);
            $service->apply($draft);

            // Assert
            $this->expectException(UpgradeDraftException::class);

            // Act
            $service->apply($draft);
        } finally {
            $this->tearDownCleanup();
        }
    }

    #[Test]
    public function apply_givenDraftMissingRequiredEnemyAndOriginalIsPublished_throwsExceptionAndDoesNotMutateOriginal(): void
    {
        $newMappingVersion = null;

        try {
            // Arrange
            [$original, , $newMappingVersion] = $this->createOutdatedRoute();
            $originalTitle                    = $original->title;

            $service = $this->buildUpgradeDraftService();
            $draft   = $service->findOrCreateDraft($original);

            // A required enemy on the draft's (new) mapping version that the draft has not killed - the
            // same invariant DungeonRoutePolicy::publish() enforces
            Enemy::create([
                'mapping_version_id' => $newMappingVersion->id,
                'floor_id'           => $newMappingVersion->dungeon->floors->first()->id,
                'npc_id'             => null,
                'teeming'            => null,
                'required'           => true,
                'lat'                => 0,
                'lng'                => 0,
            ]);

            // Act
            try {
                $service->apply($draft);
                $this->fail('Expected an UpgradeDraftException to be thrown');
            } catch (UpgradeDraftException $upgradeDraftException) {
                // Assert
                $this->assertSame(
                    __('policy.apply_upgrade_draft_not_all_required_enemies_killed'),
                    $upgradeDraftException->getMessage(),
                );
            }

            $original->refresh();
            $this->assertSame($originalTitle, $original->title, 'A rejected Apply must not mutate the original');
            $this->assertTrue(
                DungeonRoute::query()->whereKey($draft->id)->exists(),
                'A rejected Apply must not delete the draft',
            );
        } finally {
            if ($newMappingVersion !== null) {
                Enemy::query()->where('mapping_version_id', $newMappingVersion->id)->delete();
            }
            $this->tearDownCleanup();
        }
    }

    /**
     * The Auto Route Creator applies unattended and its routes are published by construction, so a combat log that
     * missed a required enemy must not fail the whole regeneration the way a manual Apply would (#4297).
     */
    #[Test]
    public function apply_givenDraftMissingRequiredEnemyAndInvariantNotEnforced_appliesAnyway(): void
    {
        $newMappingVersion = null;

        try {
            // Arrange
            [$original, , $newMappingVersion] = $this->createOutdatedRoute();
            $originalId                       = $original->id;
            $draftId                          = null;

            $service = $this->buildUpgradeDraftService();
            $draft   = $service->findOrCreateDraft($original);
            $draftId = $draft->id;
            $draft->update(['title' => 'Applied without the invariant']);

            Enemy::create([
                'mapping_version_id' => $newMappingVersion->id,
                'floor_id'           => $newMappingVersion->dungeon->floors->first()->id,
                'npc_id'             => null,
                'teeming'            => null,
                'required'           => true,
                'lat'                => 0,
                'lng'                => 0,
            ]);

            // Act
            $applied = $service->apply($draft, enforcePublishInvariant: false);

            // Assert
            $this->assertSame($originalId, $applied->id);
            $this->assertSame('Applied without the invariant', $applied->title, 'The draft\'s content must have been applied');
            $this->assertNull(DungeonRoute::find($draftId), 'The draft must be gone once applied');
        } finally {
            if ($newMappingVersion !== null) {
                Enemy::query()->where('mapping_version_id', $newMappingVersion->id)->delete();
            }
            $this->tearDownCleanup();
        }
    }

    /**
     * Losing the draft to a concurrent apply, discard or take-over is its own exception type: the Auto Route Creator
     * has to tell it apart from the refusals it cannot retry.
     */
    #[Test]
    public function apply_givenDraftDeletedConcurrently_throwsUpgradeDraftGoneException(): void
    {
        try {
            // Arrange
            [$original] = $this->createOutdatedRoute();
            $service    = $this->buildUpgradeDraftService();
            $draft      = $service->findOrCreateDraft($original);

            // Stands in for another apply, a discard, or an Auto Route Creator regeneration taking the draft over
            DungeonRoute::query()->whereKey($draft->id)->delete();

            // Assert
            $this->expectException(UpgradeDraftGoneException::class);

            // Act
            $service->apply($draft);
        } finally {
            $this->tearDownCleanup();
        }
    }

    #[Test]
    public function apply_givenNonDraft_throwsException(): void
    {
        try {
            // Arrange
            [$original] = $this->createOutdatedRoute();

            // Assert
            $this->expectException(UpgradeDraftException::class);

            // Act
            $this->buildUpgradeDraftService()->apply($original);
        } finally {
            $this->tearDownCleanup();
        }
    }

    #[Test]
    public function apply_givenDraft_queuesThumbnailRefreshOnOriginal(): void
    {
        try {
            // Arrange
            [$original] = $this->createOutdatedRoute();
            $originalId = $original->id;

            /** @var MockObject&ThumbnailServiceInterface $thumbnailService */
            $thumbnailService = $this->createMockPublic(ThumbnailServiceInterface::class);
            $thumbnailService->expects($this->once())
                ->method('queueThumbnailRefresh')
                ->with($this->callback(static fn(DungeonRoute $route): bool => $route->id === $originalId))
                ->willReturn(true);

            $service = $this->buildUpgradeDraftService($thumbnailService);
            $draft   = $service->findOrCreateDraft($original);
            $this->killRequiredEnemiesOn($draft);

            // Act + Assert (the expectation above)
            $service->apply($draft);
        } finally {
            $this->tearDownCleanup();
        }
    }

    // ------------------------------------------------------------------ discard

    #[Test]
    public function discard_givenDraft_deletesDraftAndLeavesOriginalIntact(): void
    {
        try {
            // Arrange
            [$original, $mappingVersion] = $this->createOutdatedRoute();
            $originalId                  = $original->id;

            $service = $this->buildUpgradeDraftService();
            $draft   = $service->findOrCreateDraft($original);
            $draftId = $draft->id;

            // Act
            $service->discard($draft);

            // Assert
            $this->assertNull(DungeonRoute::find($draftId));
            $survivor = DungeonRoute::find($originalId);
            $this->assertNotNull($survivor);
            $this->assertSame($mappingVersion->id, $survivor->mapping_version_id, 'The original is untouched');
        } finally {
            $this->tearDownCleanup();
        }
    }

    #[Test]
    public function discard_givenNonDraft_throwsException(): void
    {
        try {
            // Arrange
            [$original] = $this->createOutdatedRoute();

            // Assert
            $this->expectException(UpgradeDraftException::class);

            // Act
            $this->buildUpgradeDraftService()->discard($original);
        } finally {
            $this->tearDownCleanup();
        }
    }
}
