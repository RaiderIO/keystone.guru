<?php

namespace Tests\Feature\Service\LiveSession;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Enemies\OverpulledEnemy;
use App\Models\Enemy;
use App\Models\KillZone\KillZone;
use App\Models\LiveSession;
use App\Service\LiveSession\OverpulledEnemyServiceInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('LiveSession')]
#[Group('OverpulledEnemy')]
final class OverpulledEnemyServiceTest extends PublicTestCase
{
    #[Test]
    public function getRouteCorrection_givenOverpulledEnemyStoredWithoutEnemyId_returnsItsEnemyForces(): void
    {
        // Arrange
        /** @var Enemy $enemy */
        $enemy = Enemy::query()
            ->select(['enemies.*', 'npc_enemy_forces.enemy_forces as npc_enemy_forces'])
            ->join('npc_enemy_forces', static function (JoinClause $join) {
                $join->on('npc_enemy_forces.npc_id', 'enemies.npc_id')
                    ->on('npc_enemy_forces.mapping_version_id', 'enemies.mapping_version_id');
            })
            ->whereNotNull('enemies.mdt_id')
            ->whereNull('enemies.enemy_forces_override')
            ->where('npc_enemy_forces.enemy_forces', '>', 0)
            ->whereNotExists(static function (Builder $query) {
                $query->from('enemies as same_enemies')
                    ->whereColumn('same_enemies.npc_id', 'enemies.npc_id')
                    ->whereColumn('same_enemies.mdt_id', 'enemies.mdt_id')
                    ->whereColumn('same_enemies.mapping_version_id', 'enemies.mapping_version_id')
                    ->whereColumn('same_enemies.id', '!=', 'enemies.id');
            })
            ->firstOrFail();
        $expectedEnemyForces = (int)$enemy->getAttribute('npc_enemy_forces');

        $dungeonRoute = null;
        $killZone     = null;
        $liveSession  = null;

        try {
            $dungeonRoute = DungeonRoute::factory()->create([
                'dungeon_id'         => $enemy->mappingVersion->dungeon_id,
                'mapping_version_id' => $enemy->mapping_version_id,
                'enemy_forces'       => 0,
                'teeming'            => false,
            ]);
            $killZone = KillZone::factory()->create([
                'dungeon_route_id' => $dungeonRoute->id,
                'index'            => 1,
            ]);
            $liveSession = LiveSession::create([
                'dungeon_route_id' => $dungeonRoute->id,
                'user_id'          => $dungeonRoute->author_id,
                'public_key'       => LiveSession::generateRandomPublicKey(),
            ]);
            // Rows written before enemy_id was stored identify the enemy by npc_id/mdt_id only
            OverpulledEnemy::query()->insert([
                'live_session_id' => $liveSession->id,
                'kill_zone_id'    => $killZone->id,
                'npc_id'          => $enemy->npc_id,
                'mdt_id'          => $enemy->mdt_id,
                'enemy_id'        => 0,
            ]);

            // Act
            $routeCorrection = app(OverpulledEnemyServiceInterface::class)->getRouteCorrection($liveSession);

            // Assert
            $this->assertSame($expectedEnemyForces, $routeCorrection->getEnemyForces());
        } finally {
            if ($liveSession !== null) {
                OverpulledEnemy::query()->where('live_session_id', $liveSession->id)->delete();
                LiveSession::query()->whereKey($liveSession->id)->delete();
            }
            if ($killZone !== null) {
                KillZone::query()->whereKey($killZone->id)->delete();
            }
            $dungeonRoute?->delete();
        }
    }
}
