<?php

namespace Tests\Feature\Controller\Ajax;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Team;
use App\Models\TeamUser;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Teapot\StatusCode;
use Tests\Feature\Controller\DungeonRouteTestBase;

#[Group('Controller')]
#[Group('DungeonRoute')]
final class AjaxDungeonRouteCloneToTeamTest extends DungeonRouteTestBase
{
    private Team $team;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->team = Team::create([
            'name'         => sprintf('Clone to team test %s', uniqid()),
            'public_key'   => Team::generateRandomPublicKey(),
            'invite_code'  => Team::generateRandomPublicKey(12, 'invite_code'),
            'description'  => 'Created by AjaxDungeonRouteCloneToTeamTest',
            'icon_file_id' => -1,
            'default_role' => TeamUser::ROLE_MEMBER,
        ]);

        /** @var User $user */
        $user = Auth::user();

        TeamUser::create(['team_id' => $this->team->id, 'user_id' => $user->id, 'role' => TeamUser::ROLE_ADMIN]);
    }

    #[\Override]
    protected function tearDown(): void
    {
        try {
            DungeonRoute::query()->where('clone_of', $this->dungeonRoute->public_key)->get()->each->delete();

            // Team's "deleting" hook walks members->patreonAdFreeGiveaway, which trips
            // preventLazyLoading unless the chain is eager-loaded first.
            $this->team->load('members.patreonAdFreeGiveaway')->delete();
        } finally {
            parent::tearDown();
        }
    }

    #[Test]
    public function cloneToTeam_givenAValidTeam_clonesTheRouteIntoThatTeam(): void
    {
        // Act
        $response = $this->post($this->cloneToTeamUrl());

        // Assert
        $response->assertNoContent();

        /** @var DungeonRoute|null $clone */
        $clone = DungeonRoute::query()->where('clone_of', $this->dungeonRoute->public_key)->first();

        $this->assertNotNull($clone);
        $this->assertSame($this->team->id, $clone->team_id);
    }

    /**
     * Guards #4264: DungeonRouteSaveService::cloneRoute() is transacted, but the
     * $team->addRoute($newRoute) that follows it was not part of that transaction. A failure there
     * committed the clone anyway, leaving it assigned to no team at all - not drift, but a visibly
     * wrong outcome, since picking the team is the entire point of the request.
     */
    #[Test]
    public function cloneToTeam_givenTheTeamAssignmentFails_rollsBackTheWholeClone(): void
    {
        // Arrange - fail specifically the team assignment write, which is the only save on a
        // DungeonRoute that makes team_id dirty. By that point the clone and all of its relations
        // have already been inserted inside the same transaction.
        DungeonRoute::updating(static function (DungeonRoute $dungeonRoute): bool {
            if ($dungeonRoute->isDirty('team_id')) {
                throw new Exception('Simulated failure assigning the clone to the team');
            }

            return true;
        });

        try {
            // Act
            $response = $this->post($this->cloneToTeamUrl());

            // Assert - no half-cloned route was left behind for the user to stumble over
            $response->assertStatus(StatusCode::INTERNAL_SERVER_ERROR);
            $this->assertEquals(0, DungeonRoute::query()->where('clone_of', $this->dungeonRoute->public_key)->count());
        } finally {
            // Remove only the listener registered above - DungeonRoute::flushEventListeners() would
            // also wipe DungeonRoute::boot()'s own listeners for the rest of the PHPUnit process
            Event::forget('eloquent.updating: ' . DungeonRoute::class);
        }
    }

    /**
     * Guards #4264: not every failed write throws. Team::addRoute() used to hardcode its return to
     * true and ignore save()'s result, so a 'saving' listener vetoing the assignment left the
     * transaction to commit normally - the same orphan clone as above, reached without an exception
     * and therefore invisible to the test that only covers the throwing path.
     */
    #[Test]
    public function cloneToTeam_givenTheTeamAssignmentIsVetoed_rollsBackTheWholeClone(): void
    {
        // Arrange - veto rather than throw: returning false from 'saving' aborts the write silently.
        // Scoped to an update (exists === true), because on the clone's own insert every attribute
        // counts as dirty and this would veto the creation instead of the team assignment
        DungeonRoute::saving(static fn(DungeonRoute $dungeonRoute): bool => !$dungeonRoute->exists ||
            !$dungeonRoute->isDirty('team_id'));

        try {
            // Act
            $response = $this->post($this->cloneToTeamUrl());

            // Assert - no half-cloned route was left behind for the user to stumble over
            $response->assertStatus(StatusCode::INTERNAL_SERVER_ERROR);
            $this->assertEquals(0, DungeonRoute::query()->where('clone_of', $this->dungeonRoute->public_key)->count());
        } finally {
            // Remove only the listener registered above - DungeonRoute::flushEventListeners() would
            // also wipe DungeonRoute::boot()'s own listeners for the rest of the PHPUnit process
            Event::forget('eloquent.saving: ' . DungeonRoute::class);
        }
    }

    private function cloneToTeamUrl(): string
    {
        return sprintf(
            '/ajax/%s/clone/team/%s',
            $this->dungeonRoute->getRouteKey(),
            $this->team->getRouteKey(),
        );
    }
}
