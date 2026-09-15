<?php

namespace Tests\Feature\App\Service\CombatLog\Builders;

use App\Dto\Request\CombatLog\Route\CombatLogRouteRequestDto;
use App\Models\Dungeon;
use App\Models\DungeonKey;
use App\Models\Enemy;
use App\Models\KillZone\KillZone;
use App\Models\KillZone\KillZoneEnemy;
use App\Models\Mapping\MappingVersion;
use App\Repositories\Interfaces\DungeonRepositoryInterface;
use App\Repositories\Interfaces\EnemyRepositoryInterface;
use App\Repositories\Interfaces\Floor\FloorRepositoryInterface;
use App\Repositories\Interfaces\Npc\NpcRepositoryInterface;
use App\Repositories\Interfaces\SpellRepositoryInterface;
use App\Repositories\Stub\DungeonRoute\DungeonRouteAffixGroupRepository;
use App\Repositories\Stub\DungeonRoute\DungeonRouteRepository;
use App\Repositories\Stub\KillZone\KillZoneEnemyRepository;
use App\Repositories\Stub\KillZone\KillZoneRepository;
use App\Repositories\Stub\KillZone\KillZoneSpellRepository;
use App\Service\CombatLog\Builders\CombatLogRouteDungeonRouteBuilder;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\Season\SeasonAffixGroupServiceInterface;
use App\Service\Season\SeasonServiceInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\LoadsJsonFiles;
use Tests\TestCases\PublicTestCase;

/**
 * StubRepository (used by every write-side dependency here) hands out ids starting at 1, purely in-memory, so a
 * builder-made KillZone's id routinely collides with a real kill_zones row's id (#4178). KillZone::getEnemies() reads
 * a belongsToMany relation keyed by that id - if it isn't pre-populated it lazy-loads straight from the real table,
 * silently returning whatever unrelated route owns the colliding id instead of the pull this build just resolved.
 */
#[Group('CombatLog')]
#[Group('DungeonRouteBuilder')]
final class DungeonRouteBuilderStubKillZoneEnemiesTest extends PublicTestCase
{
    use LoadsJsonFiles;

    private const FIXTURE_NAME = 'TWW/tww_s1_ara_kara_city_of_echoes_3';

    private const FIXTURE_ROOT_PATH = '../../../../Controller/Api/V1/APICombatLogController/';

    /** The first id StubRepository hands out, and so the one a builder-made KillZone collides on. */
    private const COLLIDING_KILL_ZONE_ID = 1;

    #[Test]
    public function build_givenBuilderMadeKillZoneIdCollidesWithRealRow_resolvesItsOwnEnemiesInstead(): void
    {
        // Arrange
        // A real, persisted KillZone with its own real enemies attached has to sit at the colliding id - that
        // row is the entire point of the test. A freshly seeded database has one there, which is what CI runs
        // against. A database seeded more than once does not: kill zones carry no ids in dungeonroutes.json,
        // so DungeonDataSeeder re-inserts the demo routes under fresh autoincrement ids and the lowest one
        // ends up in the 1500s. That is not an edge case - update-mdt-package re-seeds on every MDT bump - so
        // provision the row rather than failing on its absence (#4311).
        $realCollidingKillZone = KillZone::query()->find(self::COLLIDING_KILL_ZONE_ID);

        try {
            $collidingRealKillZone = $realCollidingKillZone ?? $this->createCollidingKillZone();

            $this->assertBuildResolvesItsOwnEnemies($collidingRealKillZone);
        } finally {
            if ($realCollidingKillZone === null) {
                KillZoneEnemy::query()->where('kill_zone_id', self::COLLIDING_KILL_ZONE_ID)->delete();
                KillZone::query()->whereKey(self::COLLIDING_KILL_ZONE_ID)->delete();
            }
        }
    }

    /**
     * Stands in for the seeded row when the database has been re-seeded. Its enemies deliberately come from a
     * dungeon other than the fixture's, so the "did not fall back to the real row" assertion below stays
     * meaningful instead of passing by an accidental overlap with the enemies this build resolves.
     */
    private function createCollidingKillZone(): KillZone
    {
        $fixtureDungeonId = Dungeon::query()
            ->where('key', DungeonKey::ARA_KARA_CITY_OF_ECHOES->value)
            ->value('id');

        $unrelatedEnemies = Enemy::query()
            ->whereNotIn(
                'mapping_version_id',
                MappingVersion::query()->select('id')->where('dungeon_id', $fixtureDungeonId),
            )
            ->limit(2)
            ->get()
            ->all();

        $this->assertNotEmpty($unrelatedEnemies, 'Expected enemies outside the fixture\'s dungeon to attach to the colliding KillZone.');

        return KillZone::factory()
            ->withEnemies(...$unrelatedEnemies)
            ->create(['id' => self::COLLIDING_KILL_ZONE_ID]);
    }

    private function assertBuildResolvesItsOwnEnemies(KillZone $collidingRealKillZone): void
    {
        $realEnemyIdsAtCollidingId = $collidingRealKillZone->enemies()->pluck('enemies.id')->sort()->values()->all();
        $this->assertNotEmpty($realEnemyIdsAtCollidingId, 'Precondition: the colliding real KillZone must have enemies attached for this test to be meaningful.');

        $combatLogRoute = CombatLogRouteRequestDto::createFromArray(
            $this->getJsonData(self::FIXTURE_NAME, self::FIXTURE_ROOT_PATH),
        );

        $builder = new CombatLogRouteDungeonRouteBuilder(
            app(SeasonServiceInterface::class),
            app(SeasonAffixGroupServiceInterface::class),
            app(CoordinatesServiceInterface::class),
            new DungeonRouteRepository(),
            new DungeonRouteAffixGroupRepository(),
            new KillZoneRepository(),
            new KillZoneEnemyRepository(),
            new KillZoneSpellRepository(),
            app(EnemyRepositoryInterface::class),
            app(NpcRepositoryInterface::class),
            app(SpellRepositoryInterface::class),
            app(FloorRepositoryInterface::class),
            app(DungeonRepositoryInterface::class),
            $combatLogRoute,
        );

        // Act
        $dungeonRoute = $builder->build();

        // Assert
        $firstPull = $dungeonRoute->killZones->first();
        $this->assertInstanceOf(KillZone::class, $firstPull);
        $this->assertSame(self::COLLIDING_KILL_ZONE_ID, $firstPull->id, 'Precondition: the first stub-built pull must collide with the real KillZone used above.');

        $resolvedEnemyIds = $firstPull->getEnemies()->pluck('id')->sort()->values()->all();
        $expectedEnemyIds = $firstPull->killZoneEnemies->pluck('enemy_id')->sort()->values()->all();

        $this->assertSame($expectedEnemyIds, $resolvedEnemyIds, 'getEnemies() must reflect the enemies this build actually resolved for the pull.');
        $this->assertEmpty(
            array_intersect($realEnemyIdsAtCollidingId, $resolvedEnemyIds),
            'getEnemies() must not fall back to the real, unrelated KillZone that happens to share this stub-built pull\'s id.',
        );
    }
}
