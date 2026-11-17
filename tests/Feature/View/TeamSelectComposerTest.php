<?php

namespace Tests\Feature\View;

use App\Http\View\Composers\TeamSelectComposer;
use App\Models\Laratrust\Role;
use App\Models\Team;
use App\Models\TeamUser;
use App\Models\User;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('View')]
#[Group('TeamSelectComposer')]
final class TeamSelectComposerTest extends PublicTestCase
{
    #[Test]
    public function compose_givenTeamsOfDifferentRoles_offersOnlyTheTeamsTheUserMayAddRoutesTo(): void
    {
        // Arrange
        $user             = $this->createUser();
        $memberTeam       = $this->createTeamWith($user, TeamUser::ROLE_MEMBER);
        $collaboratorTeam = $this->createTeamWith($user, TeamUser::ROLE_COLLABORATOR);
        $this->actingAs($user);
        $view = view('common.team.select');

        try {
            // Act
            app(TeamSelectComposer::class)->compose($view);

            // Assert
            $this->assertSame([$collaboratorTeam->id], $this->teamIdsOf($view->getData()['teams']));
        } finally {
            $memberTeam->delete();
            $collaboratorTeam->delete();
            $user->delete();
        }
    }

    #[Test]
    public function compose_givenTheSelectedTeamIsOneTheUserMayNotAddRoutesTo_stillOffersIt(): void
    {
        // Arrange - the route already sits on that team; leaving it out would make the unchanged form submit "no team"
        $user             = $this->createUser();
        $memberTeam       = $this->createTeamWith($user, TeamUser::ROLE_MEMBER);
        $collaboratorTeam = $this->createTeamWith($user, TeamUser::ROLE_COLLABORATOR);
        $this->actingAs($user);
        $view = view('common.team.select', ['selectedId' => $memberTeam->id]);

        try {
            // Act
            app(TeamSelectComposer::class)->compose($view);

            // Assert
            $this->assertEqualsCanonicalizing(
                [$memberTeam->id, $collaboratorTeam->id],
                $this->teamIdsOf($view->getData()['teams']),
            );
        } finally {
            $memberTeam->delete();
            $collaboratorTeam->delete();
            $user->delete();
        }
    }

    /**
     * @param  Collection<int, Team> $teams
     * @return array<int, int>
     */
    private function teamIdsOf(Collection $teams): array
    {
        return $teams->pluck('id')->all();
    }

    private function createUser(): User
    {
        $user = User::factory()->create();
        $user->addRole(Role::ROLE_USER);

        return $user;
    }

    private function createTeamWith(User $user, string $role): Team
    {
        $team = Team::create([
            'public_key'   => Team::generateRandomPublicKey(),
            'name'         => sprintf('Team select composer test %s', uniqid()),
            'description'  => 'Created by TeamSelectComposerTest',
            'invite_code'  => Team::generateRandomPublicKey(12, 'invite_code'),
            'icon_file_id' => -1,
            'default_role' => TeamUser::ROLE_MEMBER,
        ]);

        $team->addMember($user, $role);

        return $team;
    }
}
