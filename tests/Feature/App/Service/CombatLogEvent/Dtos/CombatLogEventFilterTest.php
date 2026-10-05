<?php

namespace Tests\Feature\App\Service\CombatLogEvent\Dtos;

use App;
use App\Models\Affix;
use App\Models\CombatLog\CombatLogEventDataType;
use App\Models\CombatLog\CombatLogEventEventType;
use App\Models\Dungeon;
use App\Models\GameServerRegion;
use App\Models\Mapping\MappingVersion;
use App\Service\CombatLogEvent\Dtos\CombatLogEventFilter;
use App\Service\RaiderIO\Dtos\HeatmapDataFilter;
use App\Service\Season\Dtos\SeasonWeek;
use App\Service\Season\SeasonServiceInterface;
use Codeart\OpensearchLaravel\Interfaces\OpenSearchQuery;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\ProvidesDungeon;
use Tests\Fixtures\Traits\CreatesDungeon;
use Tests\TestCases\PublicTestCase;

#[Group('CombatLogEvent')]
final class CombatLogEventFilterTest extends PublicTestCase
{
    use CreatesDungeon;
    use ProvidesDungeon;

    /**
     * A mapping version's `timer_max_seconds` column is NOT NULL DEFAULT 0 - fromHeatmapDataFilter()
     * divides by it when the caller requested a timer-fraction range, so a mapping version that still
     * carries the column's default of 0 must raise a clean InvalidArgumentException rather than a
     * DivisionByZeroError (PHP-LARAVEL-8J).
     */
    #[Test]
    public function fromHeatmapDataFilter_givenMappingVersionTimerMaxSecondsIsZero_throwsInvalidArgumentException(): void
    {
        // Arrange
        $dungeon = $this->createDungeon(mappingVersionAttributes: ['timer_max_seconds' => 0]);

        $heatmapDataFilter = new HeatmapDataFilter(
            $dungeon,
            CombatLogEventEventType::NpcDeath,
            CombatLogEventDataType::PlayerPosition,
        );
        $heatmapDataFilter->setTimerFractionMin(0.0);
        $heatmapDataFilter->setTimerFractionMax(1.0);

        // Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Mapping version does not have a timer max seconds value');

        // Act
        CombatLogEventFilter::fromHeatmapDataFilter(
            App::make(SeasonServiceInterface::class),
            $heatmapDataFilter,
        );
    }

    /**
     * The fraction-to-minutes conversion was inverted ((fraction * 60) / timerSeconds instead of
     * (fraction * timerSeconds) / 60), which - for any real dungeon timer - truncated every
     * duration bound to 0 via the (int) cast, silently collapsing the requested filter range.
     */
    #[Test]
    public function fromHeatmapDataFilter_givenTimerFractionRange_computesDurationBoundsInMinutes(): void
    {
        // Arrange - a 1800 second (30 minute) timer, filtering the middle half of the run
        $dungeon = $this->createDungeon(mappingVersionAttributes: ['timer_max_seconds' => 1800]);

        $heatmapDataFilter = new HeatmapDataFilter(
            $dungeon,
            CombatLogEventEventType::NpcDeath,
            CombatLogEventDataType::PlayerPosition,
        );
        $heatmapDataFilter->setTimerFractionMin(0.25);
        $heatmapDataFilter->setTimerFractionMax(0.75);

        // Act
        $combatLogEventFilter = CombatLogEventFilter::fromHeatmapDataFilter(
            App::make(SeasonServiceInterface::class),
            $heatmapDataFilter,
        );

        // Assert - 0.25 * 1800s / 60 = 7.5 minutes, 0.75 * 1800s / 60 = 22.5 minutes
        $this->assertSame(7, $combatLogEventFilter->getDurationMin());
        $this->assertSame(22, $combatLogEventFilter->getDurationMax());
    }

    /**
     * The period bounds are true keystone leaderboard periods - the same numbers Raider.IO is handed - so the
     * date range they select must be the week that period covers. Deriving the week by subtracting the season's
     * start period got this wrong for any season whose raw start date already falls on or after the region's
     * reset (#4456).
     */
    #[Test]
    public function toOpensearchQuery_givenAPeriodRange_filtersOnTheWeeksThosePeriodsCover(): void
    {
        // Arrange
        $seasonService = App::make(SeasonServiceInterface::class);
        $region        = GameServerRegion::getUserOrDefaultRegion();

        [$dungeon, , $seasonWeeks] = $this->findDungeonWithSeasonWeeks($seasonService, $region);

        $firstSeasonWeek  = $seasonWeeks->first();
        $secondSeasonWeek = $seasonWeeks->skip(1)->first();

        $combatLogEventFilter = new CombatLogEventFilter(
            $seasonService,
            $dungeon,
            CombatLogEventEventType::NpcDeath,
            CombatLogEventDataType::PlayerPosition,
        );
        $combatLogEventFilter->setPeriodMin($firstSeasonWeek->period);
        $combatLogEventFilter->setPeriodMax($secondSeasonWeek->period);

        // Act
        $range = $this->findStartRange($combatLogEventFilter);

        // Assert
        $this->assertNotNull($range, 'Expected a start date range for the requested period range');
        $this->assertSame($firstSeasonWeek->start->getTimestamp(), $range['gte']);
        $this->assertSame($secondSeasonWeek->start->copy()->addWeek()->getTimestamp(), $range['lte']);
    }

    /**
     * A period nothing in the season falls in - a link carrying a period from a season that has since been
     * replaced - must drop the date filter rather than resolve to an arbitrary week.
     */
    #[Test]
    public function toOpensearchQuery_givenAPeriodOutsideTheSeason_addsNoDateRange(): void
    {
        // Arrange
        $seasonService = App::make(SeasonServiceInterface::class);
        $region        = GameServerRegion::getUserOrDefaultRegion();

        [$dungeon, , $seasonWeeks] = $this->findDungeonWithSeasonWeeks($seasonService, $region);

        $outsidePeriod = $seasonWeeks->last()->period + 1000;

        $combatLogEventFilter = new CombatLogEventFilter(
            $seasonService,
            $dungeon,
            CombatLogEventEventType::NpcDeath,
            CombatLogEventDataType::PlayerPosition,
        );
        $combatLogEventFilter->setPeriodMin($outsidePeriod);
        $combatLogEventFilter->setPeriodMax($outsidePeriod + 1);

        // Act
        $range = $this->findStartRange($combatLogEventFilter);

        // Assert
        $this->assertNull($range);
    }

    #[Test]
    public function toOpensearchQuery_givenAnyFilter_matchesTheDungeonAndEventType(): void
    {
        // Arrange
        $combatLogEventFilter = $this->createFilter();

        // Act
        $matches = $this->findClauses($combatLogEventFilter, 'match');

        // Assert
        $this->assertContains(['challenge_mode_id', $combatLogEventFilter->getDungeon()->challenge_mode_id], $matches);
        $this->assertContains(['event_type', CombatLogEventEventType::NpcDeath->value], $matches);
    }

    #[Test]
    public function toOpensearchQuery_givenKeyLevelPlayerDeathsAndDurationRanges_addsARangeForEach(): void
    {
        // Arrange
        $combatLogEventFilter = $this->createFilter()
            ->setKeyLevelMin(5)
            ->setKeyLevelMax(10)
            ->setPlayerDeathsMin(1)
            ->setPlayerDeathsMax(3)
            ->setDurationMin(20)
            ->setDurationMax(30);

        // Act
        $ranges = $this->findRangesByField($combatLogEventFilter);

        // Assert - durations are minutes, the indexed duration_ms is milliseconds
        $this->assertSame(['gte' => 5, 'lte' => 10], $ranges['level'] ?? null);
        $this->assertSame(['gte' => 1, 'lte' => 3], $ranges['num_deaths'] ?? null);
        $this->assertSame(['gte' => 1_200_000, 'lte' => 1_800_000], $ranges['duration_ms'] ?? null);
    }

    #[Test]
    public function toOpensearchQuery_givenOnlyTheMinimumOfEachRange_addsNoRange(): void
    {
        // Arrange
        $combatLogEventFilter = $this->createFilter()
            ->setKeyLevelMin(5)
            ->setItemLevelMin(600)
            ->setPlayerDeathsMin(1)
            ->setDurationMin(20);

        // Act
        $ranges = $this->findRangesByField($combatLogEventFilter);

        // Assert
        $this->assertSame([], $ranges);
    }

    /**
     * Only the presence of the clause is pinned here: which bounds it carries is a separate defect.
     */
    #[Test]
    public function toOpensearchQuery_givenAnItemLevelRange_addsAnAverageItemLevelRange(): void
    {
        // Arrange
        $combatLogEventFilter = $this->createFilter()
            ->setKeyLevelMin(5)
            ->setKeyLevelMax(10)
            ->setItemLevelMin(600)
            ->setItemLevelMax(650);

        // Act
        $ranges = $this->findRangesByField($combatLogEventFilter);

        // Assert
        $this->assertSame(['gte' => 600, 'lte' => 650], $ranges['average_item_level'] ?? null);
    }

    #[Test]
    #[DataProvider('toOpensearchQuery_givenARegion_matchesTheRaiderIoRegionId_DataProvider')]
    public function toOpensearchQuery_givenARegion_matchesTheRaiderIoRegionId(?string $region, ?int $expectedRegionId): void
    {
        // Arrange
        $combatLogEventFilter = $this->createFilter()->setRegion($region);

        // Act
        $regionIds = array_values(array_map(
            static fn(array $match) => $match[1],
            array_filter($this->findClauses($combatLogEventFilter, 'match'), static fn(array $match) => $match[0] === 'region_id'),
        ));

        // Assert
        $this->assertSame($expectedRegionId === null ? [] : [$expectedRegionId], $regionIds);
    }

    /**
     * @return array<string, array{0: string|null, 1: int|null}>
     */
    public static function toOpensearchQuery_givenARegion_matchesTheRaiderIoRegionId_DataProvider(): array
    {
        return [
            'world'    => [GameServerRegion::WORLD, null],
            'europe'   => [GameServerRegion::EUROPE, 3],
            'americas' => [GameServerRegion::AMERICAS, 2],
            'china'    => [GameServerRegion::CHINA, 6],
            'korea'    => [GameServerRegion::KOREA, 4],
            'taiwan'   => [GameServerRegion::TAIWAN, 5],
            'none'     => [null, 2],
        ];
    }

    #[Test]
    public function toOpensearchQuery_givenAffixes_matchesAnyOfTheirInGameIds(): void
    {
        // Arrange
        /** @var Collection<int, Affix> $affixes */
        $affixes = Affix::query()->orderBy('id')->limit(2)->get();
        $this->assertCount(2, $affixes, 'The seeded database must carry at least two affixes');
        $combatLogEventFilter = $this->createFilter()->setAffixes($affixes);

        // Act
        $affixIds = array_values(array_map(
            static fn(array $match) => $match[1],
            array_filter($this->findClauses($combatLogEventFilter, 'match'), static fn(array $match) => $match[0] === 'affix_id'),
        ));

        // Assert
        $this->assertSame($affixes->pluck('affix_id')->all(), $affixIds);
    }

    private function createFilter(): CombatLogEventFilter
    {
        [$dungeon] = $this->findDungeon(challengeMode: true);

        return new CombatLogEventFilter(
            App::make(SeasonServiceInterface::class),
            $dungeon,
            CombatLogEventEventType::NpcDeath,
            CombatLogEventDataType::PlayerPosition,
        );
    }

    /**
     * Every `range` clause the filter added to its Opensearch query, keyed by the field it ranges over.
     *
     * @return array<string, array<string, int>>
     */
    private function findRangesByField(CombatLogEventFilter $combatLogEventFilter): array
    {
        $result = [];
        foreach ($this->findClauses($combatLogEventFilter, 'range') as [$field, $range]) {
            $result[$field] = $range;
        }

        return $result;
    }

    /**
     * Every clause of the given type (`match`, `range`) the filter added to its Opensearch query, as [field, value] pairs.
     *
     * @return array<int, array{0: string, 1: mixed}>
     */
    private function findClauses(CombatLogEventFilter $combatLogEventFilter, string $type): array
    {
        $result = [];
        $walk   = static function (mixed $node) use (&$walk, &$result, $type): void {
            if ($node instanceof OpenSearchQuery) {
                $node = $node->toOpenSearchQuery();
            }

            if (!is_array($node)) {
                return;
            }

            if (isset($node[$type]) && is_array($node[$type])) {
                foreach ($node[$type] as $field => $value) {
                    $result[] = [$field, $value];
                }

                return;
            }

            foreach ($node as $child) {
                $walk($child);
            }
        };

        $walk($combatLogEventFilter->toOpensearchQuery());

        return $result;
    }

    /**
     * A dungeon whose most recent season has at least two weeks behind it, together with those weeks.
     *
     * @return array{0: Dungeon, 1: MappingVersion, 2: Collection<int, SeasonWeek>}
     */
    private function findDungeonWithSeasonWeeks(SeasonServiceInterface $seasonService, GameServerRegion $region): array
    {
        return $this->findDungeon(
            challengeMode: true,
            resolve:       static function (Dungeon $dungeon) use ($seasonService, $region): ?Collection {
                $season = $seasonService->getMostRecentSeasonForDungeon($dungeon);

                if ($season === null) {
                    return null;
                }

                $seasonWeeks = $seasonService->getSeasonWeeks($season, $region);

                return $seasonWeeks->count() >= 2 ? $seasonWeeks : null;
            },
        );
    }

    /**
     * The `start` range the filter added to its Opensearch query, or null when it added none.
     *
     * @return array<string, int>|null
     */
    private function findStartRange(CombatLogEventFilter $combatLogEventFilter): ?array
    {
        $walk = static function (mixed $node) use (&$walk): ?array {
            if ($node instanceof OpenSearchQuery) {
                $node = $node->toOpenSearchQuery();
            }

            if (!is_array($node)) {
                return null;
            }

            if (isset($node['range']['start'])) {
                return $node['range']['start'];
            }

            foreach ($node as $child) {
                $found = $walk($child);

                if ($found !== null) {
                    return $found;
                }
            }

            return null;
        };

        return $walk($combatLogEventFilter->toOpensearchQuery());
    }
}
