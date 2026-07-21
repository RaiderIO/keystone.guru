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
     * @return array<int, CombatLogParsingCriterionCheck>
     */
    private function defaultCriteria(): array
    {
        return [
            new CombatLogParsingCriterionCheck(Dungeon::class, self::DUNGEON_ID),
            new CombatLogParsingCriterionCheck(CharacterClassSpecialization::class, self::SPEC_ID),
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
            $result = $this->service->getModelsEligibleForPolling(self::VERSION, Dungeon::class, $season);

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
            $result = $this->service->getModelsEligibleForPolling(self::VERSION, Dungeon::class, $season);

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
            $result = $this->service->getModelsEligibleForPolling(self::VERSION, Dungeon::class, $season);

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
