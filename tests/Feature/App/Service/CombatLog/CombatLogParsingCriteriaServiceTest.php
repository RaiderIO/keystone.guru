<?php

namespace Tests\Feature\App\Service\CombatLog;

use App\Logic\CombatLog\CombatLogVersion;
use App\Models\CharacterClassSpecialization;
use App\Models\CombatLog\CombatLogParsingCriterion;
use App\Models\Dungeon;
use App\Models\Season;
use App\Service\CombatLog\CombatLogParsingCriteriaService;
use App\Service\CombatLog\CombatLogParsingCriteriaServiceInterface;
use App\Service\CombatLog\Dtos\CombatLogParsingCriterionCheck;
use App\Service\CombatLog\Dtos\KeyLevelBand;
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

    private CombatLogParsingCriteriaServiceInterface $service;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new CombatLogParsingCriteriaService();

        CombatLogParsingCriterion::query()
            ->whereIn('model_id', [self::DUNGEON_ID, self::SPEC_ID])
            ->delete();
    }

    #[\Override]
    protected function tearDown(): void
    {
        CombatLogParsingCriterion::query()
            ->whereIn('model_id', [self::DUNGEON_ID, self::SPEC_ID])
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
        $result = $this->service->shouldParse(self::VERSION, $this->defaultCriteria());

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
        $result = $this->service->shouldParse(self::VERSION, $this->defaultCriteria());

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
        $result = $this->service->shouldParse(self::VERSION, $this->defaultCriteria());

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
        $result = $this->service->shouldParse(self::VERSION, $this->defaultCriteria());

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function recordParsed_givenValidCriteria_incrementsBothCounters(): void
    {
        // Arrange
        $this->service->shouldParse(self::VERSION, $this->defaultCriteria()); // create rows

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
    public function shouldParse_givenYesterdayCountsAtThreshold_returnsTrue(): void
    {
        // Arrange — yesterday's rows at threshold should not affect today
        Carbon::setTestNow(Carbon::yesterday());
        $this->service->shouldParse(self::VERSION, $this->defaultCriteria());
        CombatLogParsingCriterion::query()
            ->whereIn('model_id', [self::DUNGEON_ID, self::SPEC_ID])
            ->update(['count' => 100]);
        Carbon::setTestNow(null);

        // Act
        $result = $this->service->shouldParse(self::VERSION, $this->defaultCriteria());

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
            $result = $this->service->getModelsEligibleForPolling(self::VERSION, Dungeon::class, $season, $this->band());

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
            $result = $this->service->getModelsEligibleForPolling(self::VERSION, Dungeon::class, $season, $this->band());

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
            $result = $this->service->getModelsEligibleForPolling(self::VERSION, Dungeon::class, $season, $this->band());

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
        $result = $this->service->shouldParse(self::VERSION, $this->defaultCriteria(new KeyLevelBand(7, 11)));

        // Assert — bands hold separate budgets, so a full band cannot block another one
        $this->assertTrue($result);
    }

    #[Test]
    public function shouldParse_givenTopBandCriteriaAtThreshold_returnsTrue(): void
    {
        // Arrange
        CombatLogParsingCriterion::factory()->forDungeon(self::DUNGEON_ID)->forBand(22, null)->atThreshold()->create();

        // Act
        $result = $this->service->shouldParse(self::VERSION, $this->defaultCriteria(new KeyLevelBand(22, null)));

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
        $this->service->shouldParse(self::VERSION, $this->defaultCriteria(new KeyLevelBand(2, 6)));
        $this->service->shouldParse(self::VERSION, $this->defaultCriteria(new KeyLevelBand(7, 11)));

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
        $result = $this->service->shouldParse(self::VERSION, $this->defaultCriteria(new KeyLevelBand(22, 26)));

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
            $result = $this->service->getModelsEligibleForPolling(self::VERSION, Dungeon::class, $season, new KeyLevelBand(7, 11));

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
            $result = $this->service->getModelsEligibleForPolling(self::VERSION, Dungeon::class, $season, new KeyLevelBand(22, null));

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
}
