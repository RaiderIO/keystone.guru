<?php

namespace Tests\Feature\App\Service\CombatLog;

use App\Logic\CombatLog\CombatLogVersion;
use App\Models\CharacterClassSpecialization;
use App\Models\CharacterRace;
use App\Models\CombatLog\CombatLogParsingCriterion;
use App\Models\Dungeon;
use App\Models\Season;
use App\Service\CombatLog\CombatLogParsingCriteriaService;
use App\Service\CombatLog\CombatLogParsingCriteriaServiceInterface;
use App\Service\CombatLog\DataExtractors\SpellCounters\SpellCounterDefinitionInterface;
use App\Service\CombatLog\DataExtractors\SpellCounters\SpellCounterDefinitions;
use App\Service\CombatLog\Dtos\CombatLogParsingCriterionCheck;
use App\Service\CombatLog\Dtos\KeyLevelBand;
use App\Service\CombatLog\Dtos\PollingBudgetWindow;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('CombatLogParsingCriteriaService')]
final class CombatLogParsingCriteriaServiceTest extends PublicTestCase
{
    private const int VERSION    = CombatLogVersion::RETAIL_12_0_5;
    private const int DUNGEON_ID = 999901;
    private const int SPEC_ID    = 999902;
    private const int RACE_ID    = 999904;

    private CombatLogParsingCriteriaServiceInterface $service;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new CombatLogParsingCriteriaService();

        CombatLogParsingCriterion::query()
            ->whereIn('model_id', [self::DUNGEON_ID, self::SPEC_ID, self::RACE_ID])
            ->delete();
    }

    #[\Override]
    protected function tearDown(): void
    {
        CombatLogParsingCriterion::query()
            ->whereIn('model_id', [self::DUNGEON_ID, self::SPEC_ID, self::RACE_ID])
            ->delete();

        Carbon::setTestNow(null);

        parent::tearDown();
    }

    /**
     * The band the factory creates rows in by default.
     */
    private function band(): KeyLevelBand
    {
        return new KeyLevelBand(2, 6);
    }

    /**
     * @return array<int, CombatLogParsingCriterionCheck>
     */
    private function defaultCriteria(?KeyLevelBand $band = null): array
    {
        $band ??= $this->band();

        return [
            new CombatLogParsingCriterionCheck(Dungeon::class, self::DUNGEON_ID, $band),
            new CombatLogParsingCriterionCheck(CharacterClassSpecialization::class, self::SPEC_ID, $band),
        ];
    }

    #[Test]
    public function shouldParse_givenNoPriorActivity_returnsTrue(): void
    {
        // Arrange — no rows exist yet

        // Act
        $result = $this->service->shouldParse(self::VERSION, $this->defaultCriteria(), PollingBudgetWindow::full());

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function shouldParse_givenBothCountsBelowThreshold_returnsTrue(): void
    {
        // Arrange
        CombatLogParsingCriterion::factory()->forDungeon(self::DUNGEON_ID)->withCount(50)->create();
        CombatLogParsingCriterion::factory()->forClassSpec(self::SPEC_ID)->withCount(50)->create();

        // Act
        $result = $this->service->shouldParse(self::VERSION, $this->defaultCriteria(), PollingBudgetWindow::full());

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function shouldParse_givenDungeonCountAtThreshold_returnsFalse(): void
    {
        // Arrange
        CombatLogParsingCriterion::factory()->forDungeon(self::DUNGEON_ID)->atThreshold()->create();
        CombatLogParsingCriterion::factory()->forClassSpec(self::SPEC_ID)->withCount(0)->create();

        // Act
        $result = $this->service->shouldParse(self::VERSION, $this->defaultCriteria(), PollingBudgetWindow::full());

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function shouldParse_givenClassSpecCountAtThreshold_returnsFalse(): void
    {
        // Arrange
        CombatLogParsingCriterion::factory()->forDungeon(self::DUNGEON_ID)->withCount(0)->create();
        CombatLogParsingCriterion::factory()->forClassSpec(self::SPEC_ID)->atThreshold()->create();

        // Act
        $result = $this->service->shouldParse(self::VERSION, $this->defaultCriteria(), PollingBudgetWindow::full());

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function recordParsed_givenValidCriteria_incrementsBothCounters(): void
    {
        // Arrange
        $this->service->shouldParse(self::VERSION, $this->defaultCriteria(), PollingBudgetWindow::full()); // create rows

        // Act
        $this->service->recordParsed(self::VERSION, $this->defaultCriteria());

        // Assert
        $this->assertEquals(
            1,
            CombatLogParsingCriterion::query()->where('model_id', self::DUNGEON_ID)->value('count'),
        );
        $this->assertEquals(
            1,
            CombatLogParsingCriterion::query()->where('model_id', self::SPEC_ID)->value('count'),
        );
    }

    #[Test]
    public function releaseParsed_givenRecordedCriteria_decrementsBothCounters(): void
    {
        // Arrange
        $today = Carbon::now()->toDateString();
        $this->service->recordParsed(self::VERSION, $this->defaultCriteria(), $today);

        // Act
        $this->service->releaseParsed(self::VERSION, $this->defaultCriteria(), $today);

        // Assert - the run yielded nothing, so its budget is available to the next poll again
        $this->assertEquals(
            0,
            CombatLogParsingCriterion::query()->where('model_id', self::DUNGEON_ID)->value('count'),
        );
        $this->assertEquals(
            0,
            CombatLogParsingCriterion::query()->where('model_id', self::SPEC_ID)->value('count'),
        );
    }

    #[Test]
    public function releaseParsed_givenCountAlreadyZero_leavesCountAtZero(): void
    {
        // Arrange - resetAllForToday() can land between the record and the release
        CombatLogParsingCriterion::factory()->forDungeon(self::DUNGEON_ID)->withCount(0)->create();

        // Act
        $this->service->releaseParsed(
            self::VERSION,
            [new CombatLogParsingCriterionCheck(Dungeon::class, self::DUNGEON_ID, $this->band())],
            Carbon::now()->toDateString(),
        );

        // Assert
        $this->assertEquals(
            0,
            CombatLogParsingCriterion::query()->where('model_id', self::DUNGEON_ID)->value('count'),
        );
    }

    #[Test]
    public function releaseParsed_givenNoRowForThatDate_createsNoRow(): void
    {
        // Arrange - a job that fails after midnight releases against the date it was recorded on
        $yesterday = Carbon::yesterday()->toDateString();
        CombatLogParsingCriterion::factory()->forDungeon(self::DUNGEON_ID)->withCount(5)->create();

        // Act
        $this->service->releaseParsed(
            self::VERSION,
            [new CombatLogParsingCriterionCheck(Dungeon::class, self::DUNGEON_ID, $this->band())],
            $yesterday,
        );

        // Assert - yesterday's row is gone, and today's must not have paid for it
        $this->assertEquals(0, CombatLogParsingCriterion::query()
            ->where('model_id', self::DUNGEON_ID)
            ->where('date', $yesterday)
            ->count());
        $this->assertEquals(5, CombatLogParsingCriterion::query()
            ->where('model_id', self::DUNGEON_ID)
            ->where('date', Carbon::now()->toDateString())
            ->value('count'));
    }

    #[Test]
    public function releaseParsed_givenTopBandCriteria_decrementsTopBandRowOnly(): void
    {
        // Arrange - dispatchRun() records top band runs too, so releasing must mirror that exactly
        CombatLogParsingCriterion::factory()->forDungeon(self::DUNGEON_ID)->forBand(2, 6)->withCount(10)->create();
        $today = Carbon::now()->toDateString();
        $this->service->recordParsed(self::VERSION, $this->defaultCriteria(new KeyLevelBand(22, null)), $today);

        // Act
        $this->service->releaseParsed(self::VERSION, $this->defaultCriteria(new KeyLevelBand(22, null)), $today);

        // Assert
        $this->assertEquals(0, CombatLogParsingCriterion::query()
            ->where('model_id', self::DUNGEON_ID)
            ->where('mythic_level_min', 22)
            ->value('count'));
        $this->assertEquals(10, CombatLogParsingCriterion::query()
            ->where('model_id', self::DUNGEON_ID)
            ->where('mythic_level_min', 2)
            ->value('count'));
    }

    #[Test]
    public function shouldParse_givenYesterdayCountsAtThreshold_returnsTrue(): void
    {
        // Arrange — yesterday's rows at threshold should not affect today
        Carbon::setTestNow(Carbon::yesterday());
        $this->service->shouldParse(self::VERSION, $this->defaultCriteria(), PollingBudgetWindow::full());
        CombatLogParsingCriterion::query()
            ->whereIn('model_id', [self::DUNGEON_ID, self::SPEC_ID])
            ->update(['count' => 100]);
        Carbon::setTestNow(null);

        // Act
        $result = $this->service->shouldParse(self::VERSION, $this->defaultCriteria(), PollingBudgetWindow::full());

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function resetAllForToday_givenExistingCounts_resetsCountsToZero(): void
    {
        // Arrange
        CombatLogParsingCriterion::factory()->forDungeon(self::DUNGEON_ID)->withCount(75)->create();
        CombatLogParsingCriterion::factory()->forClassSpec(self::SPEC_ID)->withCount(50)->create();

        // Act
        $this->service->resetAllForToday();

        // Assert
        $this->assertEquals(
            0,
            CombatLogParsingCriterion::query()->where('model_id', self::DUNGEON_ID)->value('count'),
        );
        $this->assertEquals(
            0,
            CombatLogParsingCriterion::query()->where('model_id', self::SPEC_ID)->value('count'),
        );
    }

    #[Test]
    public function getAllModelsForCriteria_givenDungeonClass_returnsSeasonDungeons(): void
    {
        // Arrange
        $season = Season::query()->has('dungeons')->firstOrFail();

        // Act
        $result = $this->service->getAllModelsForCriteria(Dungeon::class, $season);

        // Assert
        $this->assertNotEmpty($result);
        $this->assertContainsOnlyInstancesOf(Dungeon::class, $result->all());
    }

    #[Test]
    public function getAllModelsForCriteria_givenSpecClass_returnsAllSpecs(): void
    {
        // Arrange
        $season = Season::query()->firstOrFail();

        // Act
        $result = $this->service->getAllModelsForCriteria(CharacterClassSpecialization::class, $season);

        // Assert
        $this->assertNotEmpty($result);
        $this->assertContainsOnlyInstancesOf(CharacterClassSpecialization::class, $result->all());
    }

    /**
     * Spending polling budget on a race nothing reads data for buys nothing, so the criterion races
     * are exactly the races a racial spell counter is defined for - today Night Elf alone (#4357).
     */
    #[Test]
    public function getAllModelsForCriteria_givenRaceClass_returnsOnlyRacesWithARacialSpellCounter(): void
    {
        // Arrange
        $season           = Season::query()->firstOrFail();
        $expectedRaceKeys = SpellCounterDefinitions::all()
            ->map(fn(SpellCounterDefinitionInterface $definition): ?string => $definition->getCharacterRaceKey())
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();

        // Act
        $result = $this->service->getAllModelsForCriteria(CharacterRace::class, $season);

        // Assert
        $this->assertNotEmpty($expectedRaceKeys, 'No racial spell counter is defined, so this test proves nothing');
        $this->assertContainsOnlyInstancesOf(CharacterRace::class, $result->all());
        $this->assertSame($expectedRaceKeys, $result->pluck('key')->sort()->values()->all());
    }

    /**
     * The filter is built straight off the race's faction, so it must come back eager loaded - a
     * lazy load throws under this application's lazy loading configuration.
     */
    #[Test]
    public function getAllModelsForCriteria_givenRaceClass_eagerLoadsTheFaction(): void
    {
        // Arrange
        $season = Season::query()->firstOrFail();

        // Act
        $result = $this->service->getAllModelsForCriteria(CharacterRace::class, $season);

        // Assert
        $this->assertNotEmpty($result);
        /** @var CharacterRace $race */
        foreach ($result as $race) {
            $this->assertTrue($race->relationLoaded('faction'));
        }
    }

    #[Test]
    public function shouldParse_givenRaceCriterionAtThreshold_returnsFalse(): void
    {
        // Arrange
        $check = new CombatLogParsingCriterionCheck(CharacterRace::class, self::RACE_ID, $this->band());

        CombatLogParsingCriterion::factory()
            ->forRace(self::RACE_ID, self::VERSION)
            ->create(['count' => 5, 'threshold' => 5]);

        // Act
        $result = $this->service->shouldParse(self::VERSION, [$check], PollingBudgetWindow::full());

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function recordParsed_givenRaceCriterion_incrementsItsCount(): void
    {
        // Arrange
        $check = new CombatLogParsingCriterionCheck(CharacterRace::class, self::RACE_ID, $this->band());

        // Act
        $this->service->recordParsed(self::VERSION, [$check]);

        // Assert
        $row = CombatLogParsingCriterion::query()
            ->where('model_class', CharacterRace::class)
            ->where('model_id', self::RACE_ID)
            ->firstOrFail();
        $this->assertSame(1, $row->count);
    }

    #[Test]
    public function getModelsEligibleForPolling_givenNoRowsExist_returnsAllSeasonDungeons(): void
    {
        // Arrange
        $season = Season::query()->has('dungeons')->firstOrFail();
        /** @var Dungeon $dungeon */
        $dungeon = $season->dungeons()->firstOrFail();

        try {
            CombatLogParsingCriterion::query()
                ->where('model_class', Dungeon::class)
                ->where('model_id', $dungeon->id)
                ->where('date', Carbon::now()->toDateString())
                ->delete();

            // Act
            $result = $this->service->getModelsEligibleForPolling(self::VERSION, Dungeon::class, $season, $this->band(), PollingBudgetWindow::full());

            // Assert — all season dungeons are eligible when no rows exist
            $this->assertNotEmpty($result);
            $this->assertTrue($result->contains('id', $dungeon->id));
            $this->assertContainsOnlyInstancesOf(Dungeon::class, $result->all());
        } finally {
            CombatLogParsingCriterion::query()
                ->where('model_class', Dungeon::class)
                ->where('model_id', $dungeon->id)
                ->delete();
        }
    }

    #[Test]
    public function getModelsEligibleForPolling_givenDungeonAtThreshold_excludesDungeon(): void
    {
        // Arrange
        $season = Season::query()->has('dungeons')->firstOrFail();
        /** @var Dungeon $dungeon */
        $dungeon = $season->dungeons()->firstOrFail();

        try {
            CombatLogParsingCriterion::factory()->forDungeon($dungeon->id)->atThreshold()->create();

            // Act
            $result = $this->service->getModelsEligibleForPolling(self::VERSION, Dungeon::class, $season, $this->band(), PollingBudgetWindow::full());

            // Assert — dungeon at threshold is excluded
            $this->assertFalse($result->contains('id', $dungeon->id));
        } finally {
            CombatLogParsingCriterion::query()
                ->where('model_class', Dungeon::class)
                ->where('model_id', $dungeon->id)
                ->delete();
        }
    }

    #[Test]
    public function getModelsEligibleForPolling_givenDungeonBelowThreshold_includesDungeon(): void
    {
        // Arrange
        $season = Season::query()->has('dungeons')->firstOrFail();
        /** @var Dungeon $dungeon */
        $dungeon = $season->dungeons()->firstOrFail();

        try {
            CombatLogParsingCriterion::factory()->forDungeon($dungeon->id)->withCount(50)->create();

            // Act
            $result = $this->service->getModelsEligibleForPolling(self::VERSION, Dungeon::class, $season, $this->band(), PollingBudgetWindow::full());

            // Assert — dungeon below threshold is still included
            $this->assertTrue($result->contains('id', $dungeon->id));
        } finally {
            CombatLogParsingCriterion::query()
                ->where('model_class', Dungeon::class)
                ->where('model_id', $dungeon->id)
                ->delete();
        }
    }

    #[Test]
    public function shouldParse_givenOtherBandAtThreshold_returnsTrue(): void
    {
        // Arrange — the 2-6 band is full, but we are asking about 7-11
        CombatLogParsingCriterion::factory()->forDungeon(self::DUNGEON_ID)->forBand(2, 6)->atThreshold()->create();
        CombatLogParsingCriterion::factory()->forClassSpec(self::SPEC_ID)->forBand(2, 6)->atThreshold()->create();

        // Act
        $result = $this->service->shouldParse(self::VERSION, $this->defaultCriteria(new KeyLevelBand(7, 11)), PollingBudgetWindow::full());

        // Assert — bands hold separate budgets, so a full band cannot block another one
        $this->assertTrue($result);
    }

    #[Test]
    public function shouldParse_givenTopBandCriteriaAtThreshold_returnsTrue(): void
    {
        // Arrange
        CombatLogParsingCriterion::factory()->forDungeon(self::DUNGEON_ID)->forBand(22, null)->atThreshold()->create();

        // Act
        $result = $this->service->shouldParse(self::VERSION, $this->defaultCriteria(new KeyLevelBand(22, null)), PollingBudgetWindow::full());

        // Assert — the top band is always parsed, no matter its count
        $this->assertTrue($result);
    }

    #[Test]
    public function recordParsed_givenTopBandCriteria_leavesSpreadBandCountsUntouched(): void
    {
        // Arrange
        CombatLogParsingCriterion::factory()->forDungeon(self::DUNGEON_ID)->forBand(2, 6)->withCount(10)->create();

        // Act
        $this->service->recordParsed(self::VERSION, $this->defaultCriteria(new KeyLevelBand(22, null)));

        // Assert — always-parsed runs count into their own row and cannot starve the spread bands
        $this->assertEquals(10, CombatLogParsingCriterion::query()
            ->where('model_id', self::DUNGEON_ID)
            ->where('mythic_level_min', 2)
            ->value('count'));
        $this->assertEquals(1, CombatLogParsingCriterion::query()
            ->where('model_id', self::DUNGEON_ID)
            ->where('mythic_level_min', 22)
            ->value('count'));
    }

    #[Test]
    public function shouldParse_givenThresholdConfiguredForOtherBand_usesConfiguredDefaultForNewBand(): void
    {
        // Arrange — a threshold was raised by hand for the 2-6 band yesterday
        Carbon::setTestNow(Carbon::yesterday());
        CombatLogParsingCriterion::factory()->forDungeon(self::DUNGEON_ID)->forBand(2, 6)->create(['threshold' => 300]);
        Carbon::setTestNow(null);

        // Act — today's rows are created for both bands
        $this->service->shouldParse(self::VERSION, $this->defaultCriteria(new KeyLevelBand(2, 6)), PollingBudgetWindow::full());
        $this->service->shouldParse(self::VERSION, $this->defaultCriteria(new KeyLevelBand(7, 11)), PollingBudgetWindow::full());

        // Assert — 2-6 inherits its own configured threshold, 7-11 falls back to the configured default
        $this->assertEquals(300, CombatLogParsingCriterion::query()
            ->where('model_id', self::DUNGEON_ID)
            ->where('mythic_level_min', 2)
            ->where('date', Carbon::now()->toDateString())
            ->value('threshold'));
        $this->assertEquals(
            (int)config('keystoneguru.raider_io.combat_log_polling.bands.default_threshold'),
            CombatLogParsingCriterion::query()
                ->where('model_id', self::DUNGEON_ID)
                ->where('mythic_level_min', 7)
                ->value('threshold'),
        );
    }

    /**
     * Thresholds are edited per criterion row on the admin page, so they are per model per band.
     * Inheriting across models would apply one dungeon's hand-raised threshold to every other
     * dungeon whose row for that band happens to be created afterwards.
     */
    #[Test]
    public function shouldParse_givenThresholdConfiguredForAnotherModelInSameBand_usesConfiguredDefault(): void
    {
        // Arrange - a threshold was raised by hand yesterday, but for a different dungeon
        $otherDungeonId = 999899;
        Carbon::setTestNow(Carbon::yesterday());
        CombatLogParsingCriterion::factory()->forDungeon($otherDungeonId)->forBand(2, 6)->create(['threshold' => 300]);
        Carbon::setTestNow(null);

        try {
            // Act
            $this->service->shouldParse(self::VERSION, $this->defaultCriteria(new KeyLevelBand(2, 6)), PollingBudgetWindow::full());

            // Assert
            $this->assertEquals(
                (int)config('keystoneguru.raider_io.combat_log_polling.bands.default_threshold'),
                CombatLogParsingCriterion::query()
                    ->where('model_id', self::DUNGEON_ID)
                    ->where('mythic_level_min', 2)
                    ->where('date', Carbon::now()->toDateString())
                    ->value('threshold'),
            );
        } finally {
            CombatLogParsingCriterion::query()->where('model_id', $otherDungeonId)->delete();
        }
    }

    /**
     * Top band rows carry a meaningless threshold of 0 and share mythic_level_min with whichever
     * spread band starts on that level once the max key level of the season rises. Inheriting that
     * 0 would leave the new spread band at its budget from the moment it is created, every day.
     */
    #[Test]
    public function shouldParse_givenBandThatUsedToBeTheTopBand_ignoresItsThresholdOfZero(): void
    {
        // Arrange — yesterday 22+ was the always-parsed top band
        Carbon::setTestNow(Carbon::yesterday());
        CombatLogParsingCriterion::factory()->forDungeon(self::DUNGEON_ID)->forBand(22, null)->create(['threshold' => 0]);
        Carbon::setTestNow(null);

        // Act — the max key level rose, so today 22-26 is an ordinary budgeted band
        $result = $this->service->shouldParse(self::VERSION, $this->defaultCriteria(new KeyLevelBand(22, 26)), PollingBudgetWindow::full());

        // Assert
        $this->assertTrue($result);
        $this->assertEquals(
            (int)config('keystoneguru.raider_io.combat_log_polling.bands.default_threshold'),
            CombatLogParsingCriterion::query()
                ->where('model_id', self::DUNGEON_ID)
                ->where('mythic_level_min', 22)
                ->where('date', Carbon::now()->toDateString())
                ->value('threshold'),
        );
    }

    #[Test]
    public function getModelsEligibleForPolling_givenDungeonAtThresholdInOtherBand_includesDungeon(): void
    {
        // Arrange
        $season = Season::query()->has('dungeons')->firstOrFail();
        /** @var Dungeon $dungeon */
        $dungeon = $season->dungeons()->firstOrFail();

        try {
            CombatLogParsingCriterion::factory()->forDungeon($dungeon->id)->forBand(2, 6)->atThreshold()->create();

            // Act
            $result = $this->service->getModelsEligibleForPolling(self::VERSION, Dungeon::class, $season, new KeyLevelBand(7, 11), PollingBudgetWindow::full());

            // Assert — being full in one band says nothing about another
            $this->assertTrue($result->contains('id', $dungeon->id));
        } finally {
            CombatLogParsingCriterion::query()
                ->where('model_class', Dungeon::class)
                ->where('model_id', $dungeon->id)
                ->delete();
        }
    }

    #[Test]
    public function getModelsEligibleForPolling_givenTopBandAndDungeonAtThreshold_includesDungeon(): void
    {
        // Arrange
        $season = Season::query()->has('dungeons')->firstOrFail();
        /** @var Dungeon $dungeon */
        $dungeon = $season->dungeons()->firstOrFail();

        try {
            CombatLogParsingCriterion::factory()->forDungeon($dungeon->id)->forBand(22, null)->atThreshold()->create();

            // Act
            $result = $this->service->getModelsEligibleForPolling(self::VERSION, Dungeon::class, $season, new KeyLevelBand(22, null), PollingBudgetWindow::full());

            // Assert — nothing is ever excluded from the top band
            $this->assertTrue($result->contains('id', $dungeon->id));
        } finally {
            CombatLogParsingCriterion::query()
                ->where('model_class', Dungeon::class)
                ->where('model_id', $dungeon->id)
                ->delete();
        }
    }

    #[Test]
    public function resetAllForToday_givenYesterdayCounts_doesNotResetYesterdayRows(): void
    {
        // Arrange
        Carbon::setTestNow(Carbon::yesterday());
        CombatLogParsingCriterion::factory()->forDungeon(self::DUNGEON_ID)->withCount(75)->create();
        Carbon::setTestNow(null);

        // Act
        $this->service->resetAllForToday();

        // Assert — yesterday's row is untouched
        $this->assertEquals(
            75,
            CombatLogParsingCriterion::query()->where('model_id', self::DUNGEON_ID)->value('count'),
        );
    }

    /**
     * The factory's threshold is 100, so a 1/6 window releases 17 (100 * 1 / 6, rounded up).
     */
    #[Test]
    public function shouldParse_givenCountAtTheFirstOpportunitysShare_returnsFalse(): void
    {
        // Arrange
        CombatLogParsingCriterion::factory()->forDungeon(self::DUNGEON_ID)->withCount(17)->create();
        CombatLogParsingCriterion::factory()->forClassSpec(self::SPEC_ID)->withCount(0)->create();

        // Act
        $result = $this->service->shouldParse(self::VERSION, $this->defaultCriteria(), new PollingBudgetWindow(1, 6));

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function shouldParse_givenCountJustBelowTheFirstOpportunitysShare_returnsTrue(): void
    {
        // Arrange
        CombatLogParsingCriterion::factory()->forDungeon(self::DUNGEON_ID)->withCount(16)->create();
        CombatLogParsingCriterion::factory()->forClassSpec(self::SPEC_ID)->withCount(0)->create();

        // Act
        $result = $this->service->shouldParse(self::VERSION, $this->defaultCriteria(), new PollingBudgetWindow(1, 6));

        // Assert
        $this->assertTrue($result);
    }

    /**
     * The heart of #4359: a count that was allowed by the full daily budget must be refused early
     * in the day, or the whole budget is spent in the first hours and the sample is skewed to
     * whichever region happens to be in prime time then.
     */
    #[Test]
    public function shouldParse_givenCountBelowThresholdButAboveTheEarlyShare_returnsFalse(): void
    {
        // Arrange — 50 of 100 is fine for the day, but not at the first of six opportunities
        CombatLogParsingCriterion::factory()->forDungeon(self::DUNGEON_ID)->withCount(50)->create();
        CombatLogParsingCriterion::factory()->forClassSpec(self::SPEC_ID)->withCount(0)->create();

        // Act
        $earlyResult = $this->service->shouldParse(self::VERSION, $this->defaultCriteria(), new PollingBudgetWindow(1, 6));
        $lateResult  = $this->service->shouldParse(self::VERSION, $this->defaultCriteria(), new PollingBudgetWindow(4, 6));

        // Assert
        $this->assertFalse($earlyResult);
        $this->assertTrue($lateResult);
    }

    /**
     * The whole daily budget must remain reachable: the last opportunity of the day releases
     * exactly the threshold, not one short of it.
     */
    #[Test]
    public function shouldParse_givenTheLastOpportunityOfTheDay_releasesTheEntireThreshold(): void
    {
        // Arrange
        CombatLogParsingCriterion::factory()->forDungeon(self::DUNGEON_ID)->withCount(99)->create();
        CombatLogParsingCriterion::factory()->forClassSpec(self::SPEC_ID)->withCount(0)->create();

        // Act
        $result = $this->service->shouldParse(self::VERSION, $this->defaultCriteria(), new PollingBudgetWindow(6, 6));

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function shouldParse_givenTheLastOpportunityOfTheDayAndCountAtThreshold_returnsFalse(): void
    {
        // Arrange
        CombatLogParsingCriterion::factory()->forDungeon(self::DUNGEON_ID)->atThreshold()->create();
        CombatLogParsingCriterion::factory()->forClassSpec(self::SPEC_ID)->withCount(0)->create();

        // Act
        $result = $this->service->shouldParse(self::VERSION, $this->defaultCriteria(), new PollingBudgetWindow(6, 6));

        // Assert
        $this->assertFalse($result);
    }

    /**
     * A single spread band gets one opportunity per hour, which is the (hour + 1) / 24 gate the
     * issue describes. Hour 0 releases 5 of 100, hour 23 releases all of it.
     */
    #[Test]
    public function shouldParse_givenASingleBandAtHourZeroAndHour23_gatesOnOneTwentyFourthPerHour(): void
    {
        // Arrange
        CombatLogParsingCriterion::factory()->forDungeon(self::DUNGEON_ID)->withCount(5)->create();
        CombatLogParsingCriterion::factory()->forClassSpec(self::SPEC_ID)->withCount(0)->create();

        // Act
        $hour0  = $this->service->shouldParse(self::VERSION, $this->defaultCriteria(), new PollingBudgetWindow(1, 24));
        $hour23 = $this->service->shouldParse(self::VERSION, $this->defaultCriteria(), new PollingBudgetWindow(24, 24));

        // Assert
        $this->assertFalse($hour0);
        $this->assertTrue($hour23);
    }

    #[Test]
    public function shouldParse_givenTopBandCriteriaAndAnEarlyWindow_returnsTrue(): void
    {
        // Arrange
        CombatLogParsingCriterion::factory()->forDungeon(self::DUNGEON_ID)->forBand(22, null)->atThreshold()->create();

        // Act
        $result = $this->service->shouldParse(self::VERSION, $this->defaultCriteria(new KeyLevelBand(22, null)), new PollingBudgetWindow(1, 6));

        // Assert — the top band has no budget to spread
        $this->assertTrue($result);
    }

    #[Test]
    public function getModelsEligibleForPolling_givenDungeonAtTheEarlyShareButBelowThreshold_excludesDungeon(): void
    {
        // Arrange
        $season = Season::query()->has('dungeons')->firstOrFail();
        /** @var Dungeon $dungeon */
        $dungeon = $season->dungeons()->firstOrFail();

        try {
            CombatLogParsingCriterion::factory()->forDungeon($dungeon->id)->withCount(50)->create();

            // Act
            $early = $this->service->getModelsEligibleForPolling(self::VERSION, Dungeon::class, $season, $this->band(), new PollingBudgetWindow(1, 6));
            $late  = $this->service->getModelsEligibleForPolling(self::VERSION, Dungeon::class, $season, $this->band(), new PollingBudgetWindow(6, 6));

            // Assert — the eligible count the command logs tracks the same ceiling shouldParse() uses
            $this->assertFalse($early->contains('id', $dungeon->id));
            $this->assertTrue($late->contains('id', $dungeon->id));
        } finally {
            CombatLogParsingCriterion::query()
                ->where('model_class', Dungeon::class)
                ->where('model_id', $dungeon->id)
                ->delete();
        }
    }

    /**
     * The admin panel's reset button (AdminToolsCombatLogCriteriaController::criteriaReset) can be
     * pressed at any hour, so the count and the ceiling come from different clocks afterwards. A
     * zero count is below any positive ceiling, so a reset always hands budget back - but pro rata,
     * not all at once.
     */
    #[Test]
    public function shouldParse_givenAMidDayReset_handsBudgetBackWithinTheCurrentWindow(): void
    {
        // Arrange — a band that had spent its whole daily threshold
        CombatLogParsingCriterion::factory()->forDungeon(self::DUNGEON_ID)->atThreshold()->create();
        CombatLogParsingCriterion::factory()->forClassSpec(self::SPEC_ID)->atThreshold()->create();

        $beforeReset = $this->service->shouldParse(self::VERSION, $this->defaultCriteria(), new PollingBudgetWindow(2, 6));

        // Act
        $this->service->resetAllForToday();

        $afterReset = $this->service->shouldParse(self::VERSION, $this->defaultCriteria(), new PollingBudgetWindow(2, 6));

        // Assert
        $this->assertFalse($beforeReset);
        $this->assertTrue($afterReset);
    }
}
