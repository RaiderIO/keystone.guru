<?php

namespace Tests\Unit\Fixtures;

use App\Models\Season;
use App\Service\AffixGroup\AffixGroupEaseTierService;
use App\Service\AffixGroup\AffixGroupEaseTierServiceInterface;
use App\Service\AffixGroup\Logging\AffixGroupEaseTierServiceLoggingInterface;
use App\Service\CombatLog\CombatLogService;
use App\Service\CombatLog\CombatLogServiceInterface;
use App\Service\CombatLog\Logging\CombatLogDungeonRouteServiceLoggingInterface;
use App\Service\CombatLog\Logging\CombatLogServiceLoggingInterface;
use App\Service\CombatLog\ResultEventDungeonRouteService;
use App\Service\CombatLog\ResultEventDungeonRouteServiceInterface;
use App\Service\Coordinates\CoordinatesService;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\Expansion\ExpansionService;
use App\Service\Expansion\ExpansionServiceInterface;
use App\Service\Season\SeasonService;
use App\Service\Season\SeasonServiceInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCases\PublicTestCase;

class ServiceFixtures
{
    /**
     * @param PublicTestCase                                 $testCase
     * @param SeasonServiceInterface|null                    $seasonService
     * @param AffixGroupEaseTierServiceLoggingInterface|null $log
     * @param array                                          $methodsToMock
     * @return MockObject|AffixGroupEaseTierServiceInterface
     */
    public static function getAffixGroupEaseTierServiceMock(
        PublicTestCase                            $testCase,
        SeasonServiceInterface                    $seasonService = null,
        AffixGroupEaseTierServiceLoggingInterface $log = null,
        array                                     $methodsToMock = []

    ): MockObject {
        return $testCase
            ->getMockBuilder(AffixGroupEaseTierService::class)
            ->onlyMethods($methodsToMock)
            ->setConstructorArgs([
                $seasonService ?? self::getSeasonServiceMock($testCase),
                $log ?? LoggingFixtures::createAffixGroupEaseTierServiceLogging($testCase),
            ])
            ->getMock();
    }

    /**
     * @param PublicTestCase                   $testCase
     * @param CombatLogServiceLoggingInterface $log
     * @param array                            $methodsToMock
     * @return MockObject|CombatLogServiceInterface
     */
    public static function getCombatLogServiceMock(
        PublicTestCase                   $testCase,
        CombatLogServiceLoggingInterface $log,
        array                            $methodsToMock = []
    ): MockObject {
        return $testCase
            ->getMockBuilder(CombatLogService::class)
            ->setConstructorArgs([
                $log,
            ])
            ->onlyMethods($methodsToMock)
            ->getMock();
    }

    /**
     * @param PublicTestCase                               $testCase
     * @param CombatLogService                             $combatLogService
     * @param CombatLogDungeonRouteServiceLoggingInterface $log
     * @param array                                        $methodsToMock
     *
     * @return MockObject|ResultEventDungeonRouteServiceInterface
     */
    public static function getResultEventDungeonRouteServiceMock(
        PublicTestCase                               $testCase,
        CombatLogService                             $combatLogService,
        CombatLogDungeonRouteServiceLoggingInterface $log,
        array                                        $methodsToMock = []
    ): MockObject {
        return $testCase
            ->getMockBuilder(ResultEventDungeonRouteService::class)
            ->setConstructorArgs([
                $combatLogService,
                $log,
            ])
            ->onlyMethods($methodsToMock)
            ->getMock();
    }

    /**
     * @param PublicTestCase $testCase
     * @param array          $methodsToMock
     * @return MockObject|ExpansionServiceInterface
     */
    public static function getExpansionServiceMock(
        PublicTestCase $testCase,
        array          $methodsToMock = []
    ): MockObject {
        return $testCase->getMockBuilder(ExpansionService::class)
            ->onlyMethods($methodsToMock)
            ->getMock();
    }

    /**
     * @param PublicTestCase  $testCase
     * @param array           $methodsToMock
     * @param Collection|null $seasons
     * @return MockObject|SeasonServiceInterface
     */
    public static function getSeasonServiceMock(
        PublicTestCase $testCase,
        array          $methodsToMock = [],
        Collection     $seasons = null): SeasonServiceInterface
    {
        $methodsToMock[]          = 'getSeasons';
        $seasonServiceMockBuilder = $testCase
            ->getMockBuilder(SeasonService::class)
            ->setConstructorArgs([
                self::getExpansionServiceMock($testCase),
            ])
            ->onlyMethods($methodsToMock);

        $seasonServiceMock = $seasonServiceMockBuilder->getMock();
        $seasonServiceMock->method('getSeasons')
            ->willReturn($seasons ?? collect([
                new Season([
                    'start'             => Carbon::now()->subYear(),
                    'affix_group_count' => 12,
                ]),
            ]));

        return $seasonServiceMock;
    }

    /**
     * @param PublicTestCase $testCase
     * @param array          $methodsNotToMock
     * @return MockObject|CoordinatesServiceInterface
     */
    public static function getCoordinatesServiceMock(
        PublicTestCase $testCase,
        array          $methodsNotToMock = []
    ): MockObject {
        return $testCase
            ->getMockBuilder(CoordinatesService::class)
            ->onlyMethods($methodsNotToMock)
            ->getMock();
    }
}
