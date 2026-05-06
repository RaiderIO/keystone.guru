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
use App\Repositories\Interfaces\Npc\NpcBolsteringWhitelistRepositoryInterface;
use App\Repositories\Interfaces\Npc\NpcClassificationRepositoryInterface;
use App\Repositories\Interfaces\Npc\NpcClassRepositoryInterface;
use App\Repositories\Interfaces\Npc\NpcEnemyForcesRepositoryInterface;
use App\Repositories\Interfaces\Npc\NpcRepositoryInterface;
use App\Repositories\Interfaces\Npc\NpcSpellRepositoryInterface;
use App\Repositories\Interfaces\Npc\NpcTypeRepositoryInterface;
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
        array          $methodsToMock = [],
    ): MockObject|AffixGroupBaseRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilderPublic(AffixGroupBaseRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getAffixGroupCouplingRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = [],
    ): MockObject|AffixGroupCouplingRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilderPublic(AffixGroupCouplingRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    // Add similar methods for all other repositories
    public static function getAffixGroupEaseTierPullRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = [],
    ): MockObject|AffixGroupEaseTierPullRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilderPublic(AffixGroupEaseTierPullRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getAffixGroupEaseTierRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = [],
    ): MockObject|AffixGroupEaseTierRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilderPublic(AffixGroupEaseTierRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getAffixGroupRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = [],
    ): MockObject|AffixGroupRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilderPublic(AffixGroupRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getChallengeModeRunDataRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = [],
    ): MockObject|ChallengeModeRunDataRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilderPublic(ChallengeModeRunDataRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getChallengeModeRunRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = [],
    ): MockObject|ChallengeModeRunRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilderPublic(ChallengeModeRunRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getCombatLogEventRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = [],
    ): MockObject|CombatLogEventRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilderPublic(CombatLogEventRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getEnemyPositionRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = [],
    ): MockObject|EnemyPositionRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilderPublic(EnemyPositionRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getDungeonRouteAffixGroupRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = [],
    ): MockObject|DungeonRouteAffixGroupRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilderPublic(DungeonRouteAffixGroupRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getDungeonRouteAttributeRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = [],
    ): MockObject|DungeonRouteAttributeRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilderPublic(DungeonRouteAttributeRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getDungeonRouteEnemyRaidMarkerRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = [],
    ): MockObject|DungeonRouteEnemyRaidMarkerRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilderPublic(DungeonRouteEnemyRaidMarkerRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getDungeonRouteFavoriteRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = [],
    ): MockObject|DungeonRouteFavoriteRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilderPublic(DungeonRouteFavoriteRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getDungeonRoutePlayerClassRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = [],
    ): MockObject|DungeonRoutePlayerClassRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilderPublic(DungeonRoutePlayerClassRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getDungeonRoutePlayerRaceRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = [],
    ): MockObject|DungeonRoutePlayerRaceRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilderPublic(DungeonRoutePlayerRaceRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getDungeonRoutePlayerSpecializationRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = [],
    ): MockObject|DungeonRoutePlayerSpecializationRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilderPublic(DungeonRoutePlayerSpecializationRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getDungeonRouteRatingRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = [],
    ): MockObject|DungeonRouteRatingRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilderPublic(DungeonRouteRatingRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getDungeonRouteRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = [],
    ): MockObject|DungeonRouteRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilderPublic(DungeonRouteRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getDungeonRouteThumbnailJobRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = [],
    ): MockObject|DungeonRouteThumbnailJobRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilderPublic(DungeonRouteThumbnailJobRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getOverpulledEnemyRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = [],
    ): MockObject|OverpulledEnemyRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilderPublic(OverpulledEnemyRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getPridefulEnemyRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = [],
    ): MockObject|PridefulEnemyRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilderPublic(PridefulEnemyRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getFloorCouplingRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = [],
    ): MockObject|FloorCouplingRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilderPublic(FloorCouplingRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getFloorRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = [],
    ): MockObject|FloorRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilderPublic(FloorRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getFloorUnionAreaRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = [],
    ): MockObject|FloorUnionAreaRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilderPublic(FloorUnionAreaRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getFloorUnionRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = [],
    ): MockObject|FloorUnionRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilderPublic(FloorUnionRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getGameVersionRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = [],
    ): MockObject|GameVersionRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilderPublic(GameVersionRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getKillZoneEnemyRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = [],
    ): MockObject|KillZoneEnemyRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilderPublic(KillZoneEnemyRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getKillZoneRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = [],
    ): MockObject|KillZoneRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilderPublic(KillZoneRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getKillZoneSpellRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = [],
    ): MockObject|KillZoneSpellRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilderPublic(KillZoneSpellRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getPermissionRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = [],
    ): MockObject|PermissionRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilderPublic(PermissionRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getRoleRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = [],
    ): MockObject|RoleRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilderPublic(RoleRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getTeamRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = [],
    ): MockObject|TeamRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilderPublic(TeamRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getMappingChangeLogRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = [],
    ): MockObject|MappingChangeLogRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilderPublic(MappingChangeLogRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getMappingCommitLogRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = [],
    ): MockObject|MappingCommitLogRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilderPublic(MappingCommitLogRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getMappingVersionRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = [],
    ): MockObject|MappingVersionRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilderPublic(MappingVersionRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getMetricAggregationRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = [],
    ): MockObject|MetricAggregationRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilderPublic(MetricAggregationRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getMetricRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = [],
    ): MockObject|MetricRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilderPublic(MetricRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getNpcEnemyForcesRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = [],
    ): MockObject|NpcEnemyForcesRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilderPublic(NpcEnemyForcesRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getOpensearchModelRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = [],
    ): MockObject|OpensearchModelRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilderPublic(OpensearchModelRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getPatreonAdFreeGiveawayRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = [],
    ): MockObject|PatreonAdFreeGiveawayRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilderPublic(PatreonAdFreeGiveawayRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getPatreonBenefitRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = [],
    ): MockObject|PatreonBenefitRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilderPublic(PatreonBenefitRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getPatreonUserBenefitRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = [],
    ): MockObject|PatreonUserBenefitRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilderPublic(PatreonUserBenefitRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getPatreonUserLinkRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = [],
    ): MockObject|PatreonUserLinkRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilderPublic(PatreonUserLinkRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getSimulationCraftRaidEventsOptionsRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = [],
    ): MockObject|SimulationCraftRaidEventsOptionsRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilderPublic(SimulationCraftRaidEventsOptionsRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getDungeonSpeedrunRequiredNpcRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = [],
    ): MockObject|DungeonSpeedrunRequiredNpcRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilderPublic(DungeonSpeedrunRequiredNpcRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getTagCategoryRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = [],
    ): MockObject|TagCategoryRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilderPublic(TagCategoryRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getTagRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = [],
    ): MockObject|TagRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilderPublic(TagRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getTimewalkingEventRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = [],
    ): MockObject|TimewalkingEventRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilderPublic(TimewalkingEventRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getAffixRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = [],
    ): MockObject|AffixRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilderPublic(AffixRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getBrushlineRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = [],
    ): MockObject|BrushlineRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilderPublic(BrushlineRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getCacheModelRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = [],
    ): MockObject|CacheModelRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilderPublic(CacheModelRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getCharacterClassRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = [],
    ): MockObject|CharacterClassRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilderPublic(CharacterClassRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getCharacterRaceRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = [],
    ): MockObject|CharacterRaceRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilderPublic(CharacterRaceRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getNpcRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = [],
    ): MockObject|NpcRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilderPublic(NpcRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getDungeonRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = [],
    ): MockObject|DungeonRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilderPublic(DungeonRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getEnemyRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = [],
    ): MockObject|EnemyRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilderPublic(EnemyRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getMapIconRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = [],
    ): MockObject|MapIconRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilderPublic(MapIconRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getMapIconTypeRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = [],
    ): MockObject|MapIconTypeRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilderPublic(MapIconTypeRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getPathRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = [],
    ): MockObject|PathRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilderPublic(PathRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getPublishedStateRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = [],
    ): MockObject|PublishedStateRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilderPublic(PublishedStateRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getRaidMarkerRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = [],
    ): MockObject|RaidMarkerRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilderPublic(RaidMarkerRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getSpellRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = [],
    ): MockObject|SpellRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilderPublic(SpellRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getCharacterClassSpecializationRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = [],
    ): MockObject|CharacterClassSpecializationRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilderPublic(CharacterClassSpecializationRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getCharacterRaceClassCouplingRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = [],
    ): MockObject|CharacterRaceClassCouplingRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilderPublic(CharacterRaceClassCouplingRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getDungeonFloorSwitchMarkerRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = [],
    ): MockObject|DungeonFloorSwitchMarkerRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilderPublic(DungeonFloorSwitchMarkerRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getEnemyActiveAuraRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = [],
    ): MockObject|EnemyActiveAuraRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilderPublic(EnemyActiveAuraRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getEnemyPackRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = [],
    ): MockObject|EnemyPackRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilderPublic(EnemyPackRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getEnemyPatrolRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = [],
    ): MockObject|EnemyPatrolRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilderPublic(EnemyPatrolRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getExpansionRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = [],
    ): MockObject|ExpansionRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilderPublic(ExpansionRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getFactionRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = [],
    ): MockObject|FactionRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilderPublic(FactionRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getFileRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = [],
    ): MockObject|FileRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilderPublic(FileRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getGameIconRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = [],
    ): MockObject|GameIconRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilderPublic(GameIconRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getGameServerRegionRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = [],
    ): MockObject|GameServerRegionRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilderPublic(GameServerRegionRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getLiveSessionRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = [],
    ): MockObject|LiveSessionRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilderPublic(LiveSessionRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getMapObjectToAwakenedObeliskLinkRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = [],
    ): MockObject|MapObjectToAwakenedObeliskLinkRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilderPublic(MapObjectToAwakenedObeliskLinkRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getMDTImportRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = [],
    ): MockObject|MDTImportRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilderPublic(MDTImportRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getMountableAreaRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = [],
    ): MockObject|MountableAreaRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilderPublic(MountableAreaRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getNpcBolsteringWhitelistRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = [],
    ): MockObject|NpcBolsteringWhitelistRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilderPublic(NpcBolsteringWhitelistRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getNpcClassificationRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = [],
    ): MockObject|NpcClassificationRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilderPublic(NpcClassificationRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getNpcClassRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = [],
    ): MockObject|NpcClassRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilderPublic(NpcClassRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getNpcSpellRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = [],
    ): MockObject|NpcSpellRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilderPublic(NpcSpellRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getNpcTypeRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = [],
    ): MockObject|NpcTypeRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilderPublic(NpcTypeRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getPageViewRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = [],
    ): MockObject|PageViewRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilderPublic(PageViewRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getPolylineRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = [],
    ): MockObject|PolylineRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilderPublic(PolylineRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getReleaseChangelogCategoryRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = [],
    ): MockObject|ReleaseChangelogCategoryRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilderPublic(ReleaseChangelogCategoryRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getReleaseChangelogChangeRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = [],
    ): MockObject|ReleaseChangelogChangeRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilderPublic(ReleaseChangelogChangeRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getReleaseChangelogRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = [],
    ): MockObject|ReleaseChangelogRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilderPublic(ReleaseChangelogRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getReleaseReportLogRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = [],
    ): MockObject|ReleaseReportLogRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilderPublic(ReleaseReportLogRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getReleaseRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = [],
    ): MockObject|ReleaseRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilderPublic(ReleaseRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getRouteAttributeRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = [],
    ): MockObject|RouteAttributeRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilderPublic(RouteAttributeRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getSeasonDungeonRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = [],
    ): MockObject|SeasonDungeonRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilderPublic(SeasonDungeonRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getSeasonRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = [],
    ): MockObject|SeasonRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilderPublic(SeasonRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getTeamUserRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = [],
    ): MockObject|TeamUserRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilderPublic(TeamUserRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getUserReportRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = [],
    ): MockObject|UserReportRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilderPublic(UserReportRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }

    public static function getUserRepositoryMock(
        PublicTestCase $testCase,
        array          $methodsToMock = [],
    ): MockObject|UserRepositoryInterface {
        $mockBuilder = $testCase->getMockBuilderPublic(UserRepositoryInterface::class);

        if (!empty($methodsToMock)) {
            $mockBuilder->onlyMethods($methodsToMock);
        }

        return $mockBuilder->getMock();
    }
}
