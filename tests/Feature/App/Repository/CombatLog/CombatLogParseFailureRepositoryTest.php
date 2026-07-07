<?php

namespace Tests\Feature\App\Repository\CombatLog;

use App\Models\CombatLog\CombatLogParseFailure;
use App\Repositories\Database\CombatLog\CombatLogParseFailureRepository;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('CombatLog')]
#[Group('CombatLogParseFailureRepository')]
final class CombatLogParseFailureRepositoryTest extends PublicTestCase
{
    private const int RUN_ID = 987654321;

    private CombatLogParseFailureRepository $repository;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new CombatLogParseFailureRepository();
    }

    protected function tearDown(): void
    {
        try {
            CombatLogParseFailure::query()->where('run_id', self::RUN_ID)->delete();
        } finally {
            parent::tearDown();
        }
    }

    #[Test]
    public function recordFailure_givenNewRunAndLine_createsRow(): void
    {
        // Arrange + Act
        $failure = $this->repository->recordFailure(
            self::RUN_ID,
            null,
            22012000005,
            257080,
            'SPELL_DAMAGE,...,"Pa',
            'Unbalanced quotes in string',
            'InvalidArgumentException',
        );

        // Assert
        $this->assertSame(self::RUN_ID, $failure->run_id);
        $this->assertSame(257080, $failure->line_number);
        $this->assertSame('SPELL_DAMAGE,...,"Pa', $failure->raw_line);
        $this->assertNull($failure->resolved_at);
    }

    #[Test]
    public function recordFailure_givenSameRunAndLine_upsertsSingleRow(): void
    {
        // Arrange
        $first = $this->repository->recordFailure(
            self::RUN_ID,
            null,
            22012000005,
            257080,
            'SPELL_DAMAGE,...,"Pa',
            'Unbalanced quotes in string',
            'InvalidArgumentException',
        );

        // Act — same run + line, updated message
        $second = $this->repository->recordFailure(
            self::RUN_ID,
            null,
            22012000005,
            257080,
            'SPELL_DAMAGE,...,"Pb',
            'Unbalanced quotes in string (again)',
            'InvalidArgumentException',
        );

        // Assert — one row, updated in place
        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, CombatLogParseFailure::query()->where('run_id', self::RUN_ID)->count());
        $this->assertSame('SPELL_DAMAGE,...,"Pb', $second->fresh()->raw_line);
    }

    #[Test]
    public function recordFailure_givenPreviouslyResolvedRunAndLine_reopensRow(): void
    {
        // Arrange — record then resolve
        $failure = $this->repository->recordFailure(
            self::RUN_ID,
            null,
            22012000005,
            257080,
            'SPELL_DAMAGE,...,"Pa',
            'Unbalanced quotes in string',
            'InvalidArgumentException',
        );
        $failure->update(['resolved_at' => now()]);

        // Act — the same failure recurs
        $this->repository->recordFailure(
            self::RUN_ID,
            null,
            22012000005,
            257080,
            'SPELL_DAMAGE,...,"Pa',
            'Unbalanced quotes in string',
            'InvalidArgumentException',
        );

        // Assert — the row is reopened
        $this->assertNull($failure->fresh()->resolved_at);
    }
}
