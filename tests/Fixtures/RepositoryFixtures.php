<?php

namespace Tests\Fixtures;


use App\Repositories\Interfaces\AffixGroup\AffixGroupBaseRepositoryInterface;
use App\Repositories\Interfaces\AffixGroup\AffixGroupCouplingRepositoryInterface;
use App\Repositories\Interfaces\AffixGroup\AffixGroupEaseTierPullRepositoryInterface;
use App\Repositories\Interfaces\AffixGroup\AffixGroupEaseTierRepositoryInterface;
use App\Repositories\Interfaces\AffixGroup\AffixGroupRepositoryInterface;
use App\Repositories\Interfaces\AffixRepositoryInterface;
use App\Repositories\Interfaces\BrushlineRepositoryInterface;
use App\Repositories\Interfaces\CacheModelRepositoryInterface;
use App\Repositories\Interfaces\CharacterClassRepositoryInterface;
use App\Repositories\Interfaces\CharacterClassSpecializationRepositoryInterface;
use App\Repositories\Interfaces\CharacterRaceClassCouplingRepositoryInterface;
use App\Repositories\Interfaces\CharacterRaceRepositoryInterface;
use App\Repositories\Interfaces\CombatLog\ChallengeModeRunDataRepositoryInterface;
use App\Repositories\Interfaces\CombatLog\ChallengeModeRunRepositoryInterface;
use App\Repositories\Interfaces\CombatLog\CombatLogEventRepositoryInterface;
use App\Repositories\Interfaces\CombatLog\EnemyPositionRepositoryInterface;
use App\Repositories\Interfaces\DungeonFloorSwitchMarkerRepositoryInterface;
use App\Repositories\Interfaces\DungeonRepositoryInterface;
use App\Repositories\Interfaces\DungeonRoute\DungeonRouteAffixGroupRepositoryInterface;
use App\Repositories\Interfaces\DungeonRoute\DungeonRouteAttributeRepositoryInterface;
use App\Repositories\Interfaces\DungeonRoute\DungeonRouteEnemyRaidMarkerRepositoryInterface;
use App\Repositories\Interfaces\DungeonRoute\DungeonRouteFavoriteRepositoryInterface;
use App\Repositories\Interfaces\DungeonRoute\DungeonRoutePlayerClassRepositoryInterface;
use App\Repositories\Interfaces\DungeonRoute\DungeonRoutePlayerRaceRepositoryInterface;
use App\Repositories\Interfaces\DungeonRoute\DungeonRoutePlayerSpecializationRepositoryInterface;
use App\Repositories\Interfaces\DungeonRoute\DungeonRouteRatingRepositoryInterface;
use App\Repositories\Interfaces\DungeonRoute\DungeonRouteRepositoryInterface;
use App\Repositories\Interfaces\DungeonRoute\DungeonRouteThumbnailJobRepositoryInterface;
use App\Repositories\Interfaces\Enemies\OverpulledEnemyRepositoryInterface;
use App\Repositories\Interfaces\Enemies\PridefulEnemyRepositoryInterface;
use App\Repositories\Interfaces\EnemyActiveAuraRepositoryInterface;
use App\Repositories\Interfaces\EnemyPackRepositoryInterface;
use App\Repositories\Interfaces\EnemyPatrolRepositoryInterface;
use App\Repositories\Interfaces\EnemyRepositoryInterface;
use App\Repositories\Interfaces\ExpansionRepositoryInterface;
use App\Repositories\Interfaces\FactionRepositoryInterface;
use App\Repositories\Interfaces\FileRepositoryInterface;
use App\Repositories\Interfaces\Floor\FloorCouplingRepositoryInterface;
use App\Repositories\Interfaces\Floor\FloorRepositoryInterface;
use App\Repositories\Interfaces\Floor\FloorUnionAreaRepositoryInterface;
use App\Repositories\Interfaces\Floor\FloorUnionRepositoryInterface;
use App\Repositories\Interfaces\GameIconRepositoryInterface;
use App\Repositories\Interfaces\GameServerRegionRepositoryInterface;
use App\Repositories\Interfaces\GameVersion\GameVersionRepositoryInterface;
use App\Repositories\Interfaces\KillZone\KillZoneEnemyRepositoryInterface;
use App\Repositories\Interfaces\KillZone\KillZoneRepositoryInterface;
use App\Repositories\Interfaces\KillZone\KillZoneSpellRepositoryInterface;
use App\Repositories\Interfaces\Laratrust\PermissionRepositoryInterface;
use App\Repositories\Interfaces\Laratrust\RoleRepositoryInterface;
use App\Repositories\Interfaces\LiveSessionRepositoryInterface;
use App\Repositories\Interfaces\MapIconRepositoryInterface;
use App\Repositories\Interfaces\MapIconTypeRepositoryInterface;
use App\Repositories\Interfaces\MapObjectToAwakenedObeliskLinkRepositoryInterface;
use App\Repositories\Interfaces\Mapping\MappingChangeLogRepositoryInterface;
use App\Repositories\Interfaces\Mapping\MappingCommitLogRepositoryInterface;
use App\Repositories\Interfaces\Mapping\MappingVersionRepositoryInterface;
use App\Repositories\Interfaces\MDTImportRepositoryInterface;
use App\Repositories\Interfaces\Metrics\MetricAggregationRepositoryInterface;
use App\Repositories\Interfaces\Metrics\MetricRepositoryInterface;
use App\Repositories\Interfaces\MountableAreaRepositoryInterface;
use App\Repositories\Interfaces\Npc\NpcEnemyForcesRepositoryInterface;
use App\Repositories\Interfaces\NpcBolsteringWhitelistRepositoryInterface;
use App\Repositories\Interfaces\NpcClassificationRepositoryInterface;
use App\Repositories\Interfaces\NpcClassRepositoryInterface;
use App\Repositories\Interfaces\NpcRepositoryInterface;
use App\Repositories\Interfaces\NpcSpellRepositoryInterface;
use App\Repositories\Interfaces\NpcTypeRepositoryInterface;
use App\Repositories\Interfaces\Opensearch\OpensearchModelRepositoryInterface;
use App\Repositories\Interfaces\PageViewRepositoryInterface;
use App\Repositories\Interfaces\PathRepositoryInterface;
use App\Repositories\Interfaces\Patreon\PatreonAdFreeGiveawayRepositoryInterface;
use App\Repositories\Interfaces\Patreon\PatreonBenefitRepositoryInterface;
use App\Repositories\Interfaces\Patreon\PatreonUserBenefitRepositoryInterface;
use App\Repositories\Interfaces\Patreon\PatreonUserLinkRepositoryInterface;
use App\Repositories\Interfaces\PolylineRepositoryInterface;
use App\Repositories\Interfaces\PublishedStateRepositoryInterface;
use App\Repositories\Interfaces\RaidMarkerRepositoryInterface;
use App\Repositories\Interfaces\ReleaseChangelogCategoryRepositoryInterface;
use App\Repositories\Interfaces\ReleaseChangelogChangeRepositoryInterface;
use App\Repositories\Interfaces\ReleaseChangelogRepositoryInterface;
use App\Repositories\Interfaces\ReleaseReportLogRepositoryInterface;
use App\Repositories\Interfaces\ReleaseRepositoryInterface;
use App\Repositories\Interfaces\RouteAttributeRepositoryInterface;
use App\Repositories\Interfaces\SeasonDungeonRepositoryInterface;
use App\Repositories\Interfaces\SeasonRepositoryInterface;
use App\Repositories\Interfaces\SimulationCraft\SimulationCraftRaidEventsOptionsRepositoryInterface;
use App\Repositories\Interfaces\Speedrun\DungeonSpeedrunRequiredNpcRepositoryInterface;
use App\Repositories\Interfaces\SpellRepositoryInterface;
use App\Repositories\Interfaces\Tags\TagCategoryRepositoryInterface;
use App\Repositories\Interfaces\Tags\TagRepositoryInterface;
use App\Repositories\Interfaces\TeamRepositoryInterface;
use App\Repositories\Interfaces\TeamUserRepositoryInterface;
use App\Repositories\Interfaces\Timewalking\TimewalkingEventRepositoryInterface;
use App\Repositories\Interfaces\UserReportRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCases\PublicTestCase;

class RepositoryFixtures
{
    public static function getAffixGroupBaseRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = []
    ): MockObject|AffixGroupBaseRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilder(AffixGroupBaseRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getAffixGroupCouplingRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = []
    ): MockObject|AffixGroupCouplingRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilder(AffixGroupCouplingRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    // Add similar methods for all other repositories
    public static function getAffixGroupEaseTierPullRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = []
    ): MockObject|AffixGroupEaseTierPullRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilder(AffixGroupEaseTierPullRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getAffixGroupEaseTierRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = []
    ): MockObject|AffixGroupEaseTierRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilder(AffixGroupEaseTierRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getAffixGroupRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = []
    ): MockObject|AffixGroupRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilder(AffixGroupRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getChallengeModeRunDataRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = []
    ): MockObject|ChallengeModeRunDataRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilder(ChallengeModeRunDataRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getChallengeModeRunRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = []
    ): MockObject|ChallengeModeRunRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilder(ChallengeModeRunRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getCombatLogEventRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = []
    ): MockObject|CombatLogEventRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilder(CombatLogEventRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getEnemyPositionRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = []
    ): MockObject|EnemyPositionRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilder(EnemyPositionRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getDungeonRouteAffixGroupRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = []
    ): MockObject|DungeonRouteAffixGroupRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilder(DungeonRouteAffixGroupRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getDungeonRouteAttributeRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = []
    ): MockObject|DungeonRouteAttributeRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilder(DungeonRouteAttributeRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getDungeonRouteEnemyRaidMarkerRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = []
    ): MockObject|DungeonRouteEnemyRaidMarkerRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilder(DungeonRouteEnemyRaidMarkerRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getDungeonRouteFavoriteRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = []
    ): MockObject|DungeonRouteFavoriteRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilder(DungeonRouteFavoriteRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getDungeonRoutePlayerClassRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = []
    ): MockObject|DungeonRoutePlayerClassRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilder(DungeonRoutePlayerClassRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getDungeonRoutePlayerRaceRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = []
    ): MockObject|DungeonRoutePlayerRaceRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilder(DungeonRoutePlayerRaceRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getDungeonRoutePlayerSpecializationRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = []
    ): MockObject|DungeonRoutePlayerSpecializationRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilder(DungeonRoutePlayerSpecializationRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getDungeonRouteRatingRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = []
    ): MockObject|DungeonRouteRatingRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilder(DungeonRouteRatingRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getDungeonRouteRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = []
    ): MockObject|DungeonRouteRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilder(DungeonRouteRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getDungeonRouteThumbnailJobRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = []
    ): MockObject|DungeonRouteThumbnailJobRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilder(DungeonRouteThumbnailJobRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getOverpulledEnemyRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = []
    ): MockObject|OverpulledEnemyRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilder(OverpulledEnemyRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getPridefulEnemyRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = []
    ): MockObject|PridefulEnemyRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilder(PridefulEnemyRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getFloorCouplingRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = []
    ): MockObject|FloorCouplingRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilder(FloorCouplingRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getFloorRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = []
    ): MockObject|FloorRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilder(FloorRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getFloorUnionAreaRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = []
    ): MockObject|FloorUnionAreaRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilder(FloorUnionAreaRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getFloorUnionRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = []
    ): MockObject|FloorUnionRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilder(FloorUnionRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getGameVersionRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = []
    ): MockObject|GameVersionRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilder(GameVersionRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getKillZoneEnemyRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = []
    ): MockObject|KillZoneEnemyRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilder(KillZoneEnemyRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getKillZoneRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = []
    ): MockObject|KillZoneRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilder(KillZoneRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getKillZoneSpellRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = []
    ): MockObject|KillZoneSpellRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilder(KillZoneSpellRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getPermissionRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = []
    ): MockObject|PermissionRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilder(PermissionRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getRoleRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = []
    ): MockObject|RoleRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilder(RoleRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getTeamRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = []
    ): MockObject|TeamRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilder(TeamRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getMappingChangeLogRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = []
    ): MockObject|MappingChangeLogRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilder(MappingChangeLogRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getMappingCommitLogRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = []
    ): MockObject|MappingCommitLogRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilder(MappingCommitLogRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getMappingVersionRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = []
    ): MockObject|MappingVersionRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilder(MappingVersionRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getMetricAggregationRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = []
    ): MockObject|MetricAggregationRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilder(MetricAggregationRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getMetricRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = []
    ): MockObject|MetricRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilder(MetricRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getNpcEnemyForcesRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = []
    ): MockObject|NpcEnemyForcesRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilder(NpcEnemyForcesRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getOpensearchModelRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = []
    ): MockObject|OpensearchModelRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilder(OpensearchModelRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getPatreonAdFreeGiveawayRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = []
    ): MockObject|PatreonAdFreeGiveawayRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilder(PatreonAdFreeGiveawayRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getPatreonBenefitRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = []
    ): MockObject|PatreonBenefitRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilder(PatreonBenefitRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getPatreonUserBenefitRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = []
    ): MockObject|PatreonUserBenefitRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilder(PatreonUserBenefitRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getPatreonUserLinkRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = []
    ): MockObject|PatreonUserLinkRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilder(PatreonUserLinkRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getSimulationCraftRaidEventsOptionsRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = []
    ): MockObject|SimulationCraftRaidEventsOptionsRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilder(SimulationCraftRaidEventsOptionsRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getDungeonSpeedrunRequiredNpcRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = []
    ): MockObject|DungeonSpeedrunRequiredNpcRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilder(DungeonSpeedrunRequiredNpcRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getTagCategoryRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = []
    ): MockObject|TagCategoryRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilder(TagCategoryRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getTagRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = []
    ): MockObject|TagRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilder(TagRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getTimewalkingEventRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = []
    ): MockObject|TimewalkingEventRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilder(TimewalkingEventRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getAffixRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = []
    ): MockObject|AffixRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilder(AffixRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getBrushlineRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = []
    ): MockObject|BrushlineRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilder(BrushlineRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getCacheModelRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = []
    ): MockObject|CacheModelRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilder(CacheModelRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getCharacterClassRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = []
    ): MockObject|CharacterClassRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilder(CharacterClassRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getCharacterRaceRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = []
    ): MockObject|CharacterRaceRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilder(CharacterRaceRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getNpcRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = []
    ): MockObject|NpcRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilder(NpcRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getDungeonRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = []
    ): MockObject|DungeonRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilder(DungeonRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getEnemyRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = []
    ): MockObject|EnemyRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilder(EnemyRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getMapIconRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = []
    ): MockObject|MapIconRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilder(MapIconRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getMapIconTypeRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = []
    ): MockObject|MapIconTypeRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilder(MapIconTypeRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getPathRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = []
    ): MockObject|PathRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilder(PathRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getPublishedStateRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = []
    ): MockObject|PublishedStateRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilder(PublishedStateRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getRaidMarkerRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = []
    ): MockObject|RaidMarkerRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilder(RaidMarkerRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getSpellRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = []
    ): MockObject|SpellRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilder(SpellRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getCharacterClassSpecializationRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = []
    ): MockObject|CharacterClassSpecializationRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilder(CharacterClassSpecializationRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getCharacterRaceClassCouplingRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = []
    ): MockObject|CharacterRaceClassCouplingRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilder(CharacterRaceClassCouplingRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getDungeonFloorSwitchMarkerRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = []
    ): MockObject|DungeonFloorSwitchMarkerRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilder(DungeonFloorSwitchMarkerRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getEnemyActiveAuraRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = []
    ): MockObject|EnemyActiveAuraRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilder(EnemyActiveAuraRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getEnemyPackRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = []
    ): MockObject|EnemyPackRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilder(EnemyPackRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getEnemyPatrolRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = []
    ): MockObject|EnemyPatrolRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilder(EnemyPatrolRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getExpansionRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = []
    ): MockObject|ExpansionRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilder(ExpansionRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getFactionRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = []
    ): MockObject|FactionRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilder(FactionRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getFileRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = []
    ): MockObject|FileRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilder(FileRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getGameIconRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = []
    ): MockObject|GameIconRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilder(GameIconRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getGameServerRegionRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = []
    ): MockObject|GameServerRegionRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilder(GameServerRegionRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getLiveSessionRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = []
    ): MockObject|LiveSessionRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilder(LiveSessionRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getMapObjectToAwakenedObeliskLinkRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = []
    ): MockObject|MapObjectToAwakenedObeliskLinkRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilder(MapObjectToAwakenedObeliskLinkRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getMDTImportRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = []
    ): MockObject|MDTImportRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilder(MDTImportRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getMountableAreaRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = []
    ): MockObject|MountableAreaRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilder(MountableAreaRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getNpcBolsteringWhitelistRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = []
    ): MockObject|NpcBolsteringWhitelistRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilder(NpcBolsteringWhitelistRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getNpcClassificationRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = []
    ): MockObject|NpcClassificationRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilder(NpcClassificationRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getNpcClassRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = []
    ): MockObject|NpcClassRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilder(NpcClassRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getNpcSpellRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = []
    ): MockObject|NpcSpellRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilder(NpcSpellRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getNpcTypeRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = []
    ): MockObject|NpcTypeRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilder(NpcTypeRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getPageViewRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = []
    ): MockObject|PageViewRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilder(PageViewRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getPolylineRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = []
    ): MockObject|PolylineRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilder(PolylineRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getReleaseChangelogCategoryRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = []
    ): MockObject|ReleaseChangelogCategoryRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilder(ReleaseChangelogCategoryRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }


    public static function getReleaseChangelogChangeRepositoryMock(
        PublicTestCase $testCase,
        array $methodsToMock = []
    ): MockObject|ReleaseChangelogChangeRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilder(ReleaseChangelogChangeRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getReleaseChangelogRepositoryMock(
        PublicTestCase $testCase,
        array $methodsToMock = []
    ): MockObject|ReleaseChangelogRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilder(ReleaseChangelogRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getReleaseReportLogRepositoryMock(
        PublicTestCase $testCase,
        array $methodsToMock = []
    ): MockObject|ReleaseReportLogRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilder(ReleaseReportLogRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getReleaseRepositoryMock(
        PublicTestCase $testCase,
        array $methodsToMock = []
    ): MockObject|ReleaseRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilder(ReleaseRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getRouteAttributeRepositoryMock(
        PublicTestCase $testCase,
        array $methodsToMock = []
    ): MockObject|RouteAttributeRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilder(RouteAttributeRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getSeasonDungeonRepositoryMock(
        PublicTestCase $testCase,
        array $methodsToMock = []
    ): MockObject|SeasonDungeonRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilder(SeasonDungeonRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getSeasonRepositoryMock(
        PublicTestCase $testCase,
        array $methodsToMock = []
    ): MockObject|SeasonRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilder(SeasonRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getTeamUserRepositoryMock(
        PublicTestCase $testCase,
        array $methodsToMock = []
    ): MockObject|TeamUserRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilder(TeamUserRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getUserReportRepositoryMock(
        PublicTestCase $testCase,
        array $methodsToMock = []
    ): MockObject|UserReportRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilder(UserReportRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getUserRepositoryMock(
        PublicTestCase $testCase,
        array $methodsToMock = []
    ): MockObject|UserRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilder(UserRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }
}
