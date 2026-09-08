<?php

namespace Tests\Feature\Controller\Ajax;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteChange;
use App\Models\Floor\Floor;
use App\Models\MapIcon;
use App\Models\MapIconType;
use App\Models\Mapping\MappingVersion;
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
#[Group('MapIcon')]
final class AjaxMapIconControllerTest extends DungeonRouteTestBase
{
    #[Test]
    public function delete_givenAnExistingMapIcon_deletesIt(): void
    {
        // Arrange
        $mapIcon = $this->createMapIcon();

        // Act
        $response = $this->delete($this->mapIconUrl($this->dungeonRoute, $mapIcon));

        // Assert
        $response->assertNoContent();
        $this->assertEquals(0, $this->dungeonRoute->mapicons()->count());
    }

    /**
     * Guards #4264: delete() cascaded the icon's awakened obelisk links away (MapIcon::deleting) and
     * only then wrote the change log row and touched the route, with no transaction around any of
     * it. A failure at the change log left the icon permanently gone while the route's change log -
     * the team's audit trail - had no record that it ever happened.
     */
    #[Test]
    public function delete_givenTheChangeLogWriteFails_rollsBackTheMapIconDelete(): void
    {
        // Arrange
        $mapIcon = $this->createMapIcon();

        // Fail the change log write, which is the write that follows the cascading delete
        DungeonRouteChange::creating(static function (): never {
            throw new Exception('Simulated failure writing the change log');
        });

        try {
            // Act
            $response = $this->delete($this->mapIconUrl($this->dungeonRoute, $mapIcon));

            // Assert - the client is told it failed, and the icon is still there to delete again
            $response->assertStatus(StatusCode::NOT_FOUND);
            $this->assertEquals(1, $this->dungeonRoute->mapicons()->count());
        } finally {
            // Remove only the listener registered above - DungeonRouteChange::flushEventListeners()
            // would also wipe its own boot() listeners for the rest of the PHPUnit process
            Event::forget('eloquent.creating: ' . DungeonRouteChange::class);

            $mapIcon->delete();
        }
    }

    #[Test]
    public function dungeonRouteStore_givenAMapIconOfTheDungeonRouteInTheUrl_updatesIt(): void
    {
        // Arrange
        $mapIcon = $this->createMapIcon();

        try {
            // Act
            $response = $this->put(
                $this->mapIconUrl($this->dungeonRoute, $mapIcon),
                $this->storePayload($mapIcon, 'Updated by its own dungeon route'),
            );

            // Assert
            $response->assertOk();
            $mapIcon->refresh();
            $this->assertEquals('Updated by its own dungeon route', $mapIcon->comment);
            $this->assertEquals($this->dungeonRoute->id, $mapIcon->dungeon_route_id);
        } finally {
            $mapIcon->delete();
        }
    }

    #[Test]
    public function dungeonRouteStore_givenAMapIconFromAnotherDungeonRoute_returns403(): void
    {
        // Arrange
        $otherDungeonRoute = $this->createNonFacadeDungeonRouteWithEnemies();
        $otherDungeonRoute->save();

        $mapIcon = $this->createMapIcon($otherDungeonRoute);

        try {
            // Act
            $response = $this->put(
                $this->mapIconUrl($this->dungeonRoute, $mapIcon),
                $this->storePayload($mapIcon, 'Updated through another dungeon route'),
            );

            // Assert - the icon is untouched, it does not belong to the route in the URL
            $response->assertForbidden();
            $mapIcon->refresh();
            $this->assertEquals($otherDungeonRoute->id, $mapIcon->dungeon_route_id);
            $this->assertNotEquals('Updated through another dungeon route', $mapIcon->comment);
        } finally {
            $mapIcon->delete();
            $otherDungeonRoute->delete();
        }
    }

    #[Test]
    public function dungeonRouteStore_givenAMappingMapIcon_returns403(): void
    {
        // Arrange - an icon that is part of the mapping itself, so it belongs to no dungeon route
        /** @var MappingVersion $mappingVersion */
        $mappingVersion = $this->dungeonRoute->mappingVersion;

        $mapIcon = MapIcon::factory()->create([
            'dungeon_route_id'   => null,
            'team_id'            => null,
            'mapping_version_id' => $mappingVersion->id,
            'floor_id'           => $this->randomNonFacadeFloor($this->dungeonRoute)->id,
            'map_icon_type_id'   => $this->nonAdminMapIconType()->id,
        ]);

        try {
            // Act
            $response = $this->put(
                $this->mapIconUrl($this->dungeonRoute, $mapIcon),
                $this->storePayload($mapIcon, 'Updated through a dungeon route'),
            );

            // Assert - the icon stays part of the mapping instead of being attached to the route
            $response->assertForbidden();
            $mapIcon->refresh();
            $this->assertNull($mapIcon->dungeon_route_id);
            $this->assertEquals($mappingVersion->id, $mapIcon->mapping_version_id);
        } finally {
            $mapIcon->delete();
        }
    }

    #[Test]
    public function dungeonRouteStore_givenAGuestOnASandboxDungeonRoute_updatesIt(): void
    {
        // Arrange - a sandbox route may be edited without being logged in
        $this->assertTrue($this->dungeonRoute->isSandbox());
        Auth::logout();

        $mapIcon = $this->createMapIcon();

        try {
            // Act
            $response = $this->put(
                $this->mapIconUrl($this->dungeonRoute, $mapIcon),
                $this->storePayload($mapIcon, 'Updated by a guest'),
            );

            // Assert
            $response->assertOk();
            $mapIcon->refresh();
            $this->assertEquals('Updated by a guest', $mapIcon->comment);
            $this->assertEquals($this->dungeonRoute->id, $mapIcon->dungeon_route_id);
        } finally {
            $mapIcon->delete();
        }
    }

    #[Test]
    public function dungeonRouteStore_givenATeamTheUserIsNoCollaboratorOf_leavesTheTeamUnset(): void
    {
        // Arrange
        $outsider = User::factory()->create();
        $team     = Team::create([
            'name'         => sprintf('Ajax map icon test %s', uniqid()),
            'public_key'   => Team::generateRandomPublicKey(),
            'invite_code'  => Team::generateRandomPublicKey(12, 'invite_code'),
            'description'  => 'Created by AjaxMapIconControllerTest',
            'icon_file_id' => -1,
            'default_role' => TeamUser::ROLE_MEMBER,
        ]);
        TeamUser::create([
            'team_id' => $team->id,
            'user_id' => $outsider->id,
            'role'    => TeamUser::ROLE_ADMIN,
        ]);

        $mapIcon = $this->createMapIcon();

        $payload            = $this->storePayload($mapIcon, 'Updated with a team of someone else');
        $payload['team_id'] = $team->id;

        try {
            // Act
            $response = $this->put($this->mapIconUrl($this->dungeonRoute, $mapIcon), $payload);

            // Assert - the team is only applied once the assignToTeam gate passes
            $response->assertOk();
            $mapIcon->refresh();
            $this->assertNull($mapIcon->team_id);
            $this->assertEquals($this->dungeonRoute->id, $mapIcon->dungeon_route_id);
        } finally {
            $mapIcon->delete();
            TeamUser::query()->where('team_id', $team->id)->delete();
            $team->delete();
            $outsider->delete();
        }
    }

    /** @return array<string, mixed> */
    private function storePayload(MapIcon $mapIcon, string $comment): array
    {
        return [
            'mapping_version_id'         => null,
            'floor_id'                   => $mapIcon->floor_id,
            'team_id'                    => null,
            'map_icon_type_id'           => $this->nonAdminMapIconType()->id,
            'linked_awakened_obelisk_id' => null,
            'lat'                        => $mapIcon->lat,
            'lng'                        => $mapIcon->lng,
            'comment'                    => $comment,
            'permanent_tooltip'          => $mapIcon->permanent_tooltip,
            'seasonal_index'             => $mapIcon->seasonal_index,
        ];
    }

    private function createMapIcon(?DungeonRoute $dungeonRoute = null): MapIcon
    {
        $dungeonRoute ??= $this->dungeonRoute;

        return MapIcon::factory()->create([
            'dungeon_route_id'   => $dungeonRoute->id,
            'mapping_version_id' => null,
            'floor_id'           => $this->randomNonFacadeFloor($dungeonRoute)->id,
            'map_icon_type_id'   => $this->nonAdminMapIconType()->id,
        ]);
    }

    private function nonAdminMapIconType(): MapIconType
    {
        /** @var MapIconType $mapIconType */
        $mapIconType = MapIconType::query()->where('admin_only', false)->firstOrFail();

        return $mapIconType;
    }

    private function randomNonFacadeFloor(DungeonRoute $dungeonRoute): Floor
    {
        /** @var Floor $randomFloor */
        $randomFloor = $dungeonRoute->dungeon->floors()
            ->where('facade', false)
            ->get()
            ->random();

        return $randomFloor;
    }

    private function mapIconUrl(DungeonRoute $dungeonRoute, MapIcon $mapIcon): string
    {
        return sprintf('/ajax/%s/mapicon/%s', $dungeonRoute->getRouteKey(), $mapIcon->getRouteKey());
    }
}
