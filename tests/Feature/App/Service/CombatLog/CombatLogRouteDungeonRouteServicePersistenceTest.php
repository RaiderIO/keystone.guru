<?php

namespace Tests\Feature\App\Service\CombatLog;

use App\Dto\Request\CombatLog\Route\CombatLogRouteRequestDto;
use App\Models\CombatLog\CombatLogEventEventType;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteAffixGroup;
use App\Models\KillZone\KillZone;
use App\Models\KillZone\KillZoneEnemy;
use App\Models\KillZone\KillZoneSpell;
use App\Service\CombatLog\CombatLogRouteDungeonRouteServiceInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\LoadsJsonFiles;
use Tests\TestCases\PublicTestCase;

/**
 * Guards the deliberate use of Stub repositories in CombatLogRouteDungeonRouteService: both of these flows build a
 * throwaway DungeonRoute purely as scaffolding, and must not leave anything behind in the database. Swapping the
 * Stub\* repositories for their container-bound interfaces would silently start persisting on every API call.
 */
#[Group('CombatLog')]
#[Group('CombatLogRouteDungeonRouteService')]
final class CombatLogRouteDungeonRouteServicePersistenceTest extends PublicTestCase
{
    use LoadsJsonFiles;

    private const FIXTURE_NAME = 'TWW/tww_s1_ara_kara_city_of_echoes_3';

    private const FIXTURE_ROOT_PATH = '../../../Controller/Api/V1/APICombatLogController/';

    private CombatLogRouteDungeonRouteServiceInterface $service;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(CombatLogRouteDungeonRouteServiceInterface::class);
    }

    #[Test]
    public function correctCombatLogRoute_givenValidCombatLogRoute_persistsNothing(): void
    {
        // Arrange
        $combatLogRoute = $this->getCombatLogRouteRequestDto();
        $countsBefore   = $this->getRowCounts();

        // Act
        $correctedCombatLogRoute = $this->service->correctCombatLogRoute($combatLogRoute);

        // Assert - the route was built and corrected, and still nothing of it was written
        $this->assertNotEmpty($correctedCombatLogRoute->npcs, 'The correction must have resolved npcs, or nothing was built at all');
        $this->assertSame($countsBefore, $this->getRowCounts());
    }

    #[Test]
    public function convertCombatLogRouteToCombatLogEvents_givenValidCombatLogRoute_persistsNothing(): void
    {
        // Arrange
        $combatLogRoute = $this->getCombatLogRouteRequestDto();
        $countsBefore   = $this->getRowCounts();

        // Act
        $combatLogEvents = $this->service->convertCombatLogRouteToCombatLogEvents($combatLogRoute);

        // Assert - the route was built into events, and still nothing of it was written
        $this->assertNotEmpty(
            $combatLogEvents->where('event_type', CombatLogEventEventType::NpcDeath->value),
            'The conversion must have produced an event per resolved npc, or nothing was built at all',
        );
        $this->assertSame($countsBefore, $this->getRowCounts());
    }

    private function getCombatLogRouteRequestDto(): CombatLogRouteRequestDto
    {
        return CombatLogRouteRequestDto::createFromArray(
            $this->getJsonData(self::FIXTURE_NAME, self::FIXTURE_ROOT_PATH),
        );
    }

    /**
     * @return array<class-string, int>
     */
    private function getRowCounts(): array
    {
        $result = [];

        foreach ([
            DungeonRoute::class,
            DungeonRouteAffixGroup::class,
            KillZone::class,
            KillZoneEnemy::class,
            KillZoneSpell::class,
        ] as $modelClass) {
            $result[$modelClass] = $modelClass::query()->count();
        }

        return $result;
    }
}
