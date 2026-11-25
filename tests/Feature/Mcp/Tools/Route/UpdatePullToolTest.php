<?php

namespace Tests\Feature\Mcp\Tools\Route;

use App\Logic\Structs\IngameXY;
use App\Logic\Structs\LatLng;
use App\Mcp\Servers\KeystoneGuruServer;
use App\Mcp\Tools\Route\UpdatePullTool;
use App\Models\Floor\Floor;
use App\Models\KillZone\KillZone;
use App\Models\KillZone\KillZoneSpell;
use App\Models\Spell\Spell;
use App\Service\Coordinates\CoordinatesServiceInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Mcp\McpTestCase;

#[Group('Mcp')]
#[Group('McpUpdatePull')]
final class UpdatePullToolTest extends McpTestCase
{
    #[Test]
    public function handle_givenSpellsColorAndDescription_changesOnlyThose(): void
    {
        // Arrange
        [$dungeon, $mappingVersion] = $this->findPackDungeon();
        $user                       = $this->actingAsMcpUser($this->createUser());
        $dungeonRoute               = $this->createDungeonRoute($user, $dungeon, $mappingVersion);
        [$pack]                     = $this->getPacksWithEnemies($mappingVersion, 1);
        $killZone                   = $this->createPull($dungeonRoute, 1, $pack->enemies->pluck('id')->all());
        $spell                      = Spell::query()->where('selectable', true)->orderBy('id')->firstOrFail();

        // Act
        $response = KeystoneGuruServer::tool(UpdatePullTool::class, [
            'route'       => $dungeonRoute->public_key,
            'pull_id'     => $killZone->id,
            'spell_ids'   => [$spell->id],
            'color'       => '#00ff00',
            'description' => 'Lust here',
        ]);

        // Assert
        $response->assertHasNoErrors();
        $killZone->refresh();
        $this->assertSame('#00ff00', $killZone->color);
        $this->assertSame('Lust here', $killZone->description);
        $this->assertSame([$spell->id], $killZone->killZoneSpells()->pluck('spell_id')->map(static fn($id) => (int)$id)->all());
        $this->assertEqualsCanonicalizing(
            $pack->enemies->pluck('id')->all(),
            $killZone->killZoneEnemies()->pluck('enemy_id')->map(static fn($id) => (int)$id)->all(),
            'Enemies the call did not mention stay in the pull',
        );
    }

    #[Test]
    public function handle_givenSpellNotSelectable_refusesTheSpell(): void
    {
        // Arrange
        [$dungeon, $mappingVersion] = $this->findPackDungeon();
        $user                       = $this->actingAsMcpUser($this->createUser());
        $dungeonRoute               = $this->createDungeonRoute($user, $dungeon, $mappingVersion);
        [$pack]                     = $this->getPacksWithEnemies($mappingVersion, 1);
        $killZone                   = $this->createPull($dungeonRoute, 1, $pack->enemies->pluck('id')->all());
        $spell                      = Spell::query()->where('selectable', false)->orderBy('id')->firstOrFail();

        // Act
        $response = KeystoneGuruServer::tool(UpdatePullTool::class, [
            'route'     => $dungeonRoute->public_key,
            'pull_id'   => $killZone->id,
            'spell_ids' => [$spell->id],
        ]);

        // Assert
        $response->assertHasErrors([__('mcp.error.unknown_spell_ids', ['ids' => $spell->id])]);
        $this->assertSame(0, KillZoneSpell::query()->where('kill_zone_id', $killZone->id)->count());
    }

    #[Test]
    public function handle_givenKillAreaInIngameCoordinates_storesItAsTheMapLocation(): void
    {
        // Arrange
        [$dungeon, $mappingVersion] = $this->findPackDungeon();
        $user                       = $this->actingAsMcpUser($this->createUser());
        $dungeonRoute               = $this->createDungeonRoute($user, $dungeon, $mappingVersion);
        [$pack]                     = $this->getPacksWithEnemies($mappingVersion, 1);
        $killZone                   = $this->createPull($dungeonRoute, 1, $pack->enemies->pluck('id')->all());
        /** @var Floor $floor */
        $floor = Floor::query()->whereKey($pack->floor_id)->firstOrFail();
        $x     = ($floor->ingame_min_x + $floor->ingame_max_x) / 2;
        $y     = ($floor->ingame_min_y + $floor->ingame_max_y) / 2;

        // Act
        $response = KeystoneGuruServer::tool(UpdatePullTool::class, [
            'route'     => $dungeonRoute->public_key,
            'pull_id'   => $killZone->id,
            'kill_area' => ['floor_id' => $floor->id, 'x' => $x, 'y' => $y],
        ]);

        // Assert
        $response->assertHasNoErrors();
        $killZone->refresh();
        $this->assertSame($floor->id, $killZone->floor_id);
        $roundTrip = app(CoordinatesServiceInterface::class)->calculateIngameLocationForMapLocation(
            new LatLng($killZone->lat, $killZone->lng, $floor),
        );
        $this->assertEqualsWithDelta($x, $roundTrip->getX(), 0.5);
        $this->assertEqualsWithDelta($y, $roundTrip->getY(), 0.5);
        $expectedLatLng = app(CoordinatesServiceInterface::class)->calculateMapLocationForIngameLocation(new IngameXY($x, $y, $floor));
        $this->assertEqualsWithDelta($expectedLatLng->getLat(), $killZone->lat, 0.0001);
    }

    #[Test]
    public function handle_givenFloorOfAnotherDungeon_refusesTheKillArea(): void
    {
        // Arrange
        [$dungeon, $mappingVersion] = $this->findPackDungeon();
        $user                       = $this->actingAsMcpUser($this->createUser());
        $dungeonRoute               = $this->createDungeonRoute($user, $dungeon, $mappingVersion);
        [$pack]                     = $this->getPacksWithEnemies($mappingVersion, 1);
        $killZone                   = $this->createPull($dungeonRoute, 1, $pack->enemies->pluck('id')->all());
        $foreignFloor               = Floor::query()->where('dungeon_id', '<>', $dungeon->id)->where('facade', false)->firstOrFail();

        // Act
        $response = KeystoneGuruServer::tool(UpdatePullTool::class, [
            'route'     => $dungeonRoute->public_key,
            'pull_id'   => $killZone->id,
            'kill_area' => ['floor_id' => $foreignFloor->id, 'x' => 0, 'y' => 0],
        ]);

        // Assert
        $response->assertHasErrors([__('mcp.error.unknown_floor', ['floor' => $foreignFloor->id])]);
        $this->assertNull($killZone->fresh()->floor_id);
    }

    #[Test]
    public function handle_givenClearKillArea_removesIt(): void
    {
        // Arrange
        [$dungeon, $mappingVersion] = $this->findPackDungeon();
        $user                       = $this->actingAsMcpUser($this->createUser());
        $dungeonRoute               = $this->createDungeonRoute($user, $dungeon, $mappingVersion);
        [$pack]                     = $this->getPacksWithEnemies($mappingVersion, 1);
        $killZone                   = $this->createPull($dungeonRoute, 1, $pack->enemies->pluck('id')->all());
        $enemy                      = $pack->enemies->first();
        $killZone->update(['floor_id' => $enemy->floor_id, 'lat' => $enemy->lat, 'lng' => $enemy->lng]);

        // Act
        $response = KeystoneGuruServer::tool(UpdatePullTool::class, [
            'route'           => $dungeonRoute->public_key,
            'pull_id'         => $killZone->id,
            'clear_kill_area' => true,
        ]);

        // Assert
        $response->assertHasNoErrors();
        $killZone->refresh();
        $this->assertNull($killZone->floor_id);
        $this->assertNull($killZone->lat);
    }

    #[Test]
    public function handle_givenAddedAndRemovedEnemies_changesThePullsEnemies(): void
    {
        // Arrange
        [$dungeon, $mappingVersion] = $this->findPackDungeon();
        $user                       = $this->actingAsMcpUser($this->createUser());
        $dungeonRoute               = $this->createDungeonRoute($user, $dungeon, $mappingVersion);
        [$firstPack, $secondPack]   = $this->getPacksWithEnemies($mappingVersion, 2);
        $killZone                   = $this->createPull($dungeonRoute, 1, $firstPack->enemies->pluck('id')->all());
        $removedEnemyId             = $firstPack->enemies->first()->id;

        // Act
        $response = KeystoneGuruServer::tool(UpdatePullTool::class, [
            'route'            => $dungeonRoute->public_key,
            'pull_id'          => $killZone->id,
            'add_pack_ids'     => [$secondPack->id],
            'remove_enemy_ids' => [$removedEnemyId],
        ]);

        // Assert
        $response->assertHasNoErrors();
        $expected = $firstPack->enemies->pluck('id')->merge($secondPack->enemies->pluck('id'))->reject(static fn($id) => $id === $removedEnemyId)->values()->all();
        $this->assertEqualsCanonicalizing(
            $expected,
            KillZone::findOrFail($killZone->id)->killZoneEnemies()->pluck('enemy_id')->map(static fn($id) => (int)$id)->all(),
        );
    }

    #[Test]
    public function handle_givenEnemyOfAnotherPull_refusesToAddIt(): void
    {
        // Arrange
        [$dungeon, $mappingVersion] = $this->findPackDungeon();
        $user                       = $this->actingAsMcpUser($this->createUser());
        $dungeonRoute               = $this->createDungeonRoute($user, $dungeon, $mappingVersion);
        [$firstPack, $secondPack]   = $this->getPacksWithEnemies($mappingVersion, 2);
        $killZone                   = $this->createPull($dungeonRoute, 1, $firstPack->enemies->pluck('id')->all());
        $otherEnemyId               = $secondPack->enemies->first()->id;
        $this->createPull($dungeonRoute, 2, [$otherEnemyId]);

        // Act
        $response = KeystoneGuruServer::tool(UpdatePullTool::class, [
            'route'         => $dungeonRoute->public_key,
            'pull_id'       => $killZone->id,
            'add_enemy_ids' => [$otherEnemyId],
        ]);

        // Assert
        $response->assertHasErrors([sprintf('%d => 2', $otherEnemyId)]);
        $this->assertNotContains($otherEnemyId, $killZone->killZoneEnemies()->pluck('enemy_id')->map(static fn($id) => (int)$id)->all());
    }

    #[Test]
    public function handle_givenPullOfSomeoneElsesRoute_changesNothing(): void
    {
        // Arrange
        [$dungeon, $mappingVersion] = $this->findPackDungeon();
        $user                       = $this->actingAsMcpUser($this->createUser());
        $ownRoute                   = $this->createDungeonRoute($user, $dungeon, $mappingVersion);
        $otherRoute                 = $this->createDungeonRoute($this->createUser(), $dungeon, $mappingVersion);
        [$pack]                     = $this->getPacksWithEnemies($mappingVersion, 1);
        $otherPull                  = $this->createPull($otherRoute, 1, $pack->enemies->pluck('id')->all());

        // Act
        $response = KeystoneGuruServer::tool(UpdatePullTool::class, [
            'route'   => $ownRoute->public_key,
            'pull_id' => $otherPull->id,
            'color'   => '#ffffff',
        ]);

        // Assert
        $response->assertHasErrors([__('mcp.error.pull_not_found', ['pull' => $otherPull->id])]);
        $this->assertSame('#000000', $otherPull->fresh()->color);
    }
}
