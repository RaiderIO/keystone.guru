<?php

namespace Tests\Fixtures;

use App\Models\Season;
use App\Repositories\Interfaces\SeasonRepositoryInterface;
use App\Service\AffixGroup\AffixGroupEaseTierService;
use App\Service\AffixGroup\AffixGroupEaseTierServiceInterface;
use App\Service\AffixGroup\Logging\AffixGroupEaseTierServiceLoggingInterface;
use App\Service\CombatLog\CombatLogService;
use App\Service\CombatLog\CombatLogServiceInterface;
use App\Service\CombatLog\Logging\CombatLogDungeonRouteServiceLoggingInterface;
use App\Service\CombatLog\Logging\CombatLogServiceLoggingInterface;
use App\Service\CombatLog\ResultEventDungeonRouteService;
use App\Service\CombatLog\ResultEventDungeonRouteServiceInterface;
use App\Service\CombatLogEvent\CombatLogEventService;
use App\Service\CombatLogEvent\Logging\CombatLogEventServiceLoggingInterface;
use App\Service\Coordinates\CoordinatesService;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\Expansion\ExpansionService;
use App\Service\Expansion\ExpansionServiceInterface;
use App\Service\Season\SeasonService;
use App\Service\Season\SeasonServiceInterface;
use App\Service\Spell\Logging\SpellServiceLoggingInterface;
use App\Service\Spell\SpellService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCases\PublicTestCase;

class ServiceFixtures
{
    /**
     * @throws Exception
     */
    public static function getAffixGroupEaseTierServiceMock(
        PublicTestCase                             $testCase,
        ?SeasonServiceInterface                    $seasonService = null,
        ?AffixGroupEaseTierServiceLoggingInterface $log = null,
        array                                      $methodsToMock = []

    ): MockObject|AffixGroupEaseTierServiceInterface {
        return $testCase
            ->getMockBuilder(AffixGroupEaseTierService::class)
            ->onlyMethods($methodsToMock)
            ->setConstructorArgs([
                $seasonService ?? self::getSeasonServiceMock($testCase),
                $log ?? LoggingFixtures::createAffixGroupEaseTierServiceLogging($testCase),
            ])
            ->getMock();
    }

    public static function getCombatLogServiceMock(
        PublicTestCase                   $testCase,
        CombatLogServiceLoggingInterface $log,
        array                            $methodsToMock = []
    ): MockObject|CombatLogServiceInterface {
        return $testCase
            ->getMockBuilder(CombatLogService::class)
            ->setConstructorArgs([
                $log,
            ])
            ->onlyMethods($methodsToMock)
            ->getMock();
    }

    public static function getResultEventDungeonRouteServiceMock(
        PublicTestCase                               $testCase,
        CombatLogService                             $combatLogService,
        CombatLogDungeonRouteServiceLoggingInterface $log,
        array                                        $methodsToMock = []
    ): MockObject|ResultEventDungeonRouteServiceInterface {
        return $testCase
            ->getMockBuilder(ResultEventDungeonRouteService::class)
            ->setConstructorArgs([
                $combatLogService,
                $log,
            ])
            ->onlyMethods($methodsToMock)
            ->getMock();
    }

    public static function getExpansionServiceMock(
        PublicTestCase $testCase,
        array          $methodsToMock = []
    ): MockObject|ExpansionServiceInterface {
        return $testCase->getMockBuilder(ExpansionService::class)
            ->onlyMethods($methodsToMock)
            ->getMock();
    }

    public static function getSeasonServiceMock(
        PublicTestCase            $testCase,
        SeasonRepositoryInterface $seasonRepository = null,
        array                     $methodsToMock = [],
        ?Collection               $seasons = null): MockObject|SeasonServiceInterface
    {
        $methodsToMock[]          = 'getSeasons';
        $seasonServiceMockBuilder = $testCase
            ->getMockBuilder(SeasonService::class)
            ->setConstructorArgs([
                self::getExpansionServiceMock($testCase),
                $seasonRepository ?? RepositoryFixtures::getSeasonRepositoryMock($testCase),
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

    public static function getCoordinatesServiceMock(
        PublicTestCase $testCase,
        array          $methodsToMock = []
    ): MockObject|CoordinatesServiceInterface {
        return $testCase
            ->getMockBuilder(CoordinatesService::class)
            ->onlyMethods($methodsToMock)
            ->getMock();
    }

    /**
     * @throws Exception
     */
    public static function getSpellServiceMock(
        PublicTestCase               $testCase,
        array                        $methodsToMock = [],
        SpellServiceLoggingInterface $log = null
    ): MockObject|SpellService {
        return $testCase
            ->getMockBuilder(SpellService::class)
            ->onlyMethods($methodsToMock)
            ->setConstructorArgs([
                $log ?? LoggingFixtures::createSpellServiceLogging($testCase),
            ])
            ->getMock();
    }

    /**
     * @throws Exception
     */
    public static function getCombatLogEventServiceMock(
        PublicTestCase                        $testCase,
        array                                 $methodsToMock = [],
        CoordinatesServiceInterface           $coordinatesService = null,
        CombatLogEventServiceLoggingInterface $log = null
    ): MockObject|CombatLogEventService {
        return $testCase
            ->getMockBuilder(CombatLogEventService::class)
            ->onlyMethods($methodsToMock)
            ->setConstructorArgs([
                $coordinatesService ?? ServiceFixtures::getCoordinatesServiceMock($testCase),
                $log ?? LoggingFixtures::createCombatLogEventServiceLogging($testCase),
            ])
            ->getMock();
    }
}
