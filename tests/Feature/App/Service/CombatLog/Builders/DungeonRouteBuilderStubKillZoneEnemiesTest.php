<?php

namespace Tests\Feature\App\Service\CombatLog\Builders;

use App\Dto\Request\CombatLog\Route\CombatLogRouteRequestDto;
use App\Models\KillZone\KillZone;
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

    #[Test]
    public function build_givenBuilderMadeKillZoneIdCollidesWithRealRow_resolvesItsOwnEnemiesInstead(): void
    {
        // Arrange
        // A real, persisted KillZone already exists at id 1 (seeded data) with its own real enemies attached -
        // exactly the row a stub-built pull's id collides with.
        $collidingRealKillZone     = KillZone::query()->findOrFail(1);
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
        $this->assertSame(1, $firstPull->id, 'Precondition: the first stub-built pull must collide with the real KillZone id=1 used above.');

        $resolvedEnemyIds = $firstPull->getEnemies()->pluck('id')->sort()->values()->all();
        $expectedEnemyIds = $firstPull->killZoneEnemies->pluck('enemy_id')->sort()->values()->all();

        $this->assertSame($expectedEnemyIds, $resolvedEnemyIds, 'getEnemies() must reflect the enemies this build actually resolved for the pull.');
        $this->assertEmpty(
            array_intersect($realEnemyIdsAtCollidingId, $resolvedEnemyIds),
            'getEnemies() must not fall back to the real, unrelated KillZone that happens to share this stub-built pull\'s id.',
        );
    }
}
