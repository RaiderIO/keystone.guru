<?php

namespace Tests\Feature\App\Policies;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteDraftSource;
use App\Models\PublishedState;
use App\Models\Team;
use App\Models\TeamUser;
use App\Models\User;
use App\Policies\DungeonRoutePolicy;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Policy')]
#[Group('DungeonRoute')]
final class DungeonRoutePolicyTeamDraftTest extends PublicTestCase
{
    /**
     * Every model created by a test, torn down in order.
     *
     * @var array<int, Model>
     */
    private array $cleanup = [];

    #[Test]
    public function edit_givenGuestOnDraft_returnsFalse(): void
    {
        try {
            // Arrange
            [, $draft] = $this->createTeamRouteWithDraft();

            // Act
            $mayEdit = new DungeonRoutePolicy()->edit(null, $draft);

            // Assert
            $this->assertFalse($mayEdit);
        } finally {
            $this->tearDownCleanup();
        }
    }

    #[Test]
    public function edit_givenNonOwnerOnDraft_returnsFalse(): void
    {
        try {
            // Arrange
            [, $draft] = $this->createTeamRouteWithDraft();
            $stranger  = $this->createUser();

            // Act
            $mayEdit = new DungeonRoutePolicy()->edit($stranger, $draft);

            // Assert
            $this->assertFalse($mayEdit);
        } finally {
            $this->tearDownCleanup();
        }
    }

    #[Test]
    public function edit_givenOwnerOnDraft_returnsTrue(): void
    {
        try {
            // Arrange
            [$original, $draft] = $this->createTeamRouteWithDraft();

            // Act
            $mayEdit = new DungeonRoutePolicy()->edit($original->author, $draft);

            // Assert
            $this->assertTrue($mayEdit);
        } finally {
            $this->tearDownCleanup();
        }
    }

    #[Test]
    public function edit_givenTeamCollaboratorOnDraft_returnsTrue(): void
    {
        try {
            // Arrange
            [$original, $draft] = $this->createTeamRouteWithDraft();
            $collaborator       = $this->createUser();
            $this->addMember($original->team, $collaborator, TeamUser::ROLE_COLLABORATOR);

            // Act
            $mayEdit = new DungeonRoutePolicy()->edit($collaborator, $draft->fresh());

            // Assert
            $this->assertTrue($mayEdit);
        } finally {
            $this->tearDownCleanup();
        }
    }

    #[Test]
    public function edit_givenPlainTeamMemberOnDraft_returnsFalse(): void
    {
        try {
            // Arrange
            [$original, $draft] = $this->createTeamRouteWithDraft();
            $member             = $this->createUser();
            $this->addMember($original->team, $member, TeamUser::ROLE_MEMBER);

            // Act
            $mayEdit = new DungeonRoutePolicy()->edit($member, $draft->fresh());

            // Assert
            $this->assertFalse($mayEdit);
        } finally {
            $this->tearDownCleanup();
        }
    }

    #[Test]
    public function edit_givenTeamCollaboratorOnDraftOfUnpublishedOriginal_returnsFalse(): void
    {
        try {
            // Arrange
            [$original, $draft] = $this->createTeamRouteWithDraft(PublishedState::UNPUBLISHED);
            $collaborator       = $this->createUser();
            $this->addMember($original->team, $collaborator, TeamUser::ROLE_COLLABORATOR);

            // Act
            $mayEdit = new DungeonRoutePolicy()->edit($collaborator, $draft->fresh());

            // Assert
            $this->assertFalse($mayEdit, 'A draft is never editable by someone who may not edit its original');
        } finally {
            $this->tearDownCleanup();
        }
    }

    /**
     * @return array{0: DungeonRoute, 1: DungeonRoute}
     */
    private function createTeamRouteWithDraft(string $originalPublishedState = PublishedState::TEAM): array
    {
        $author = $this->createUser();

        $team = Team::create([
            'name'         => sprintf('Draft policy test %s', uniqid()),
            'public_key'   => Team::generateRandomPublicKey(),
            'invite_code'  => Team::generateRandomPublicKey(12, 'invite_code'),
            'description'  => 'Created by DungeonRoutePolicyTeamDraftTest',
            'icon_file_id' => -1,
            'default_role' => TeamUser::ROLE_MEMBER,
        ]);
        $this->cleanup[] = $team;

        $original = DungeonRoute::factory()->create([
            'author_id'          => $author->id,
            'team_id'            => $team->id,
            'expires_at'         => null,
            'published_state_id' => PublishedState::ALL[$originalPublishedState],
        ]);
        array_unshift($this->cleanup, $original);

        $draft = DungeonRoute::factory()->create([
            'author_id'                   => $author->id,
            'team_id'                     => $team->id,
            'dungeon_id'                  => $original->dungeon_id,
            'mapping_version_id'          => $original->mapping_version_id,
            'upgrade_of_dungeon_route_id' => $original->id,
            'draft_source'                => DungeonRouteDraftSource::MdtImport,
            'expires_at'                  => null,
            'published_state_id'          => PublishedState::ALL[PublishedState::UNPUBLISHED],
        ]);
        array_unshift($this->cleanup, $draft);

        return [$original, $draft];
    }

    private function createUser(): User
    {
        $user            = User::factory()->create();
        $this->cleanup[] = $user;

        return $user;
    }

    private function addMember(Team $team, User $user, string $role): void
    {
        TeamUser::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'role'    => $role,
        ]);
    }

    private function tearDownCleanup(): void
    {
        foreach ($this->cleanup as $model) {
            $model->fresh()?->delete();
        }

        $this->cleanup = [];
    }
}
