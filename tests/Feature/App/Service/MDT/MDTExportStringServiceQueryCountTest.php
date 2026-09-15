<?php

namespace Tests\Feature\App\Service\MDT;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\KillZone\KillZone;
use App\Models\KillZone\KillZoneEnemy;
use App\Service\MDT\MDTExportStringServiceInterface;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

#[Group('UsesLua')]
#[Group('MDTExportStringService')]
#[Group('MDTExportStringServiceQueryCount')]
final class MDTExportStringServiceQueryCountTest extends MDTImportStringServiceTestBase
{
    private const int PULL_COUNT = 4;

    #[Test]
    public function getEncodedString_givenRouteWithSeveralPulls_loadsThePullEnemiesOnce(): void
    {
        $dungeonRoute = null;

        try {
            // Arrange
            $dungeonRoute = $this->createRouteWithOneEnemyPerPull();

            $killZoneEnemyQueries = 0;
            DB::listen(static function (QueryExecuted $query) use (&$killZoneEnemyQueries): void {
                if (str_contains($query->sql, 'join `kill_zone_enemies`')) {
                    $killZoneEnemyQueries++;
                }
            });

            // Act
            $encodedString = app()->make(MDTExportStringServiceInterface::class)
                ->setDungeonRoute($dungeonRoute)
                ->getEncodedString(new Collection(), false);

            // Assert
            $this->assertSame(1, $killZoneEnemyQueries);
            $this->assertCount(self::PULL_COUNT, $this->decode($encodedString)['value']['pulls']);
        } finally {
            $dungeonRoute?->delete();
        }
    }

    #[Test]
    public function getEncodedString_givenCachedExport_runsNoQueries(): void
    {
        $dungeonRoute = null;

        try {
            // Arrange
            $dungeonRoute = $this->createRouteWithOneEnemyPerPull();
            $expected     = app()->make(MDTExportStringServiceInterface::class)
                ->setDungeonRoute($dungeonRoute)
                ->getEncodedString(new Collection());

            $freshDungeonRoute = DungeonRoute::findOrFail($dungeonRoute->id);

            $queries = 0;
            DB::listen(static function () use (&$queries): void {
                $queries++;
            });

            // Act
            $encodedString = app()->make(MDTExportStringServiceInterface::class)
                ->setDungeonRoute($freshDungeonRoute)
                ->getEncodedString(new Collection());

            // Assert
            $this->assertSame(0, $queries);
            $this->assertSame($expected, $encodedString);
        } finally {
            $dungeonRoute?->delete();
        }
    }

    private function createRouteWithOneEnemyPerPull(): DungeonRoute
    {
        $dungeonRoute = $this->getMDTCompatibleDungeonRouteWithSafeEnemies(self::PULL_COUNT);

        foreach ($this->getSafeMdtEnemies($dungeonRoute, self::PULL_COUNT)->values() as $index => $enemy) {
            $killZone = KillZone::create([
                'dungeon_route_id' => $dungeonRoute->id,
                'color'            => '#ff0000',
                'index'            => $index + 1,
            ]);

            KillZoneEnemy::create([
                'kill_zone_id' => $killZone->id,
                'enemy_id'     => $enemy->id,
                'npc_id'       => $enemy->npc_id,
                'mdt_id'       => $enemy->mdt_id,
            ]);
        }

        return $dungeonRoute;
    }
}
