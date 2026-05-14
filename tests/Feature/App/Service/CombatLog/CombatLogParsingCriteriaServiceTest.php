<?php

namespace Tests\Feature\App\Service\CombatLog;

use App\Logic\CombatLog\CombatLogVersion;
use App\Models\CharacterClassSpecialization;
use App\Models\CombatLog\CombatLogParsingCriterion;
use App\Models\Dungeon;
use App\Service\CombatLog\CombatLogParsingCriteriaService;
use App\Service\CombatLog\CombatLogParsingCriteriaServiceInterface;
use App\Service\CombatLog\Dtos\CombatLogParsingCriterionCheck;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

final class CombatLogParsingCriteriaServiceTest extends PublicTestCase
{
    private const int VERSION    = CombatLogVersion::RETAIL_12_0_5;
    private const int DUNGEON_ID = 999901;
    private const int SPEC_ID    = 999902;

    private CombatLogParsingCriteriaServiceInterface $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new CombatLogParsingCriteriaService();

        CombatLogParsingCriterion::query()
            ->whereIn('model_id', [self::DUNGEON_ID, self::SPEC_ID])
            ->delete();
    }

    protected function tearDown(): void
    {
        CombatLogParsingCriterion::query()
            ->whereIn('model_id', [self::DUNGEON_ID, self::SPEC_ID])
            ->delete();

        Carbon::setTestNow(null);

        parent::tearDown();
    }

    private function defaultCriteria(): array
    {
        return [
            new CombatLogParsingCriterionCheck(Dungeon::class, self::DUNGEON_ID),
            new CombatLogParsingCriterionCheck(CharacterClassSpecialization::class, self::SPEC_ID),
        ];
    }

    #[Test]
    #[Group('CombatLogParsingCriteriaService')]
    public function shouldParse_givenNoPriorActivity_returnsTrue(): void
    {
        // Arrange — no rows exist yet

        // Act
        $result = $this->service->shouldParse(self::VERSION, $this->defaultCriteria());

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    #[Group('CombatLogParsingCriteriaService')]
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
    #[Group('CombatLogParsingCriteriaService')]
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
    #[Group('CombatLogParsingCriteriaService')]
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
    #[Group('CombatLogParsingCriteriaService')]
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
    #[Group('CombatLogParsingCriteriaService')]
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
    #[Group('CombatLogParsingCriteriaService')]
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
    #[Group('CombatLogParsingCriteriaService')]
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
