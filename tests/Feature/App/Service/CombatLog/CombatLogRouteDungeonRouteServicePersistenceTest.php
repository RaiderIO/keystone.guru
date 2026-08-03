<?php

namespace Tests\Feature\App\Service\CombatLog;

use App\Dto\Request\CombatLog\Route\CombatLogRouteRequestDto;
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
        $this->service->correctCombatLogRoute($combatLogRoute);

        // Assert
        $this->assertSame($countsBefore, $this->getRowCounts());
    }

    #[Test]
    public function convertCombatLogRouteToCombatLogEvents_givenValidCombatLogRoute_persistsNothing(): void
    {
        // Arrange
        $combatLogRoute = $this->getCombatLogRouteRequestDto();
        $countsBefore   = $this->getRowCounts();

        // Act
        $this->service->convertCombatLogRouteToCombatLogEvents($combatLogRoute);

        // Assert
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
