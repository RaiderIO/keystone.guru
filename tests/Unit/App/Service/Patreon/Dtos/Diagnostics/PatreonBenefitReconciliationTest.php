<?php

namespace Tests\Unit\App\Service\Patreon\Dtos\Diagnostics;

use App\Service\Patreon\Dtos\Diagnostics\PatreonBenefitReconciliation;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * needsAttention() reads the three counts, which are true totals over every benefit-holding link in the
 * database - not the two capped lists. That makes it untestable from the service against a shared schema,
 * where any foreign row moves the counts, so it is covered here from constructed values instead (#4488).
 */
#[Group('Patreon')]
#[Group('PatreonBenefitReconciliation')]
final class PatreonBenefitReconciliationTest extends PublicTestCase
{
    /**
     * @return array<string, array{int, int, int, bool}>
     */
    public static function reconciliationCountsProvider(): array
    {
        return [
            'nothing reported'            => [0, 0, 0, false],
            'downgrades only'             => [0, 0, 7, false],
            'an unmatched account'        => [1, 0, 0, true],
            'a blocked account'           => [0, 1, 0, true],
            'unmatched and blocked'       => [2, 3, 0, true],
            'downgrades beside a blocked' => [0, 1, 9, true],
        ];
    }

    #[Test]
    #[DataProvider('reconciliationCountsProvider')]
    public function needsAttention_givenTheReportedCounts_asksForAHumanOnlyWhenOneIsStuck(
        int  $unmatchedCount,
        int  $blockedCount,
        int  $downgradedCount,
        bool $expectedNeedsAttention,
    ): void {
        // Arrange - downgrades are excluded on purpose: the next hourly sync corrects those without help,
        // so a report that flagged them would never read clean and would stop working as a signal
        $reconciliation = new PatreonBenefitReconciliation(
            holderCount: 12,
            unmatchedCount: $unmatchedCount,
            blockedCount: $blockedCount,
            downgradedCount: $downgradedCount,
            unmatchedHolders: [],
            blockedHolders: [],
        );

        // Act
        $needsAttention = $reconciliation->needsAttention();

        // Assert
        $this->assertSame($expectedNeedsAttention, $needsAttention);
    }

    #[Test]
    public function needsAttention_givenCountsWithoutTheirHolders_stillReportsThem(): void
    {
        // Arrange - the lists are capped for display while the counts are true totals, so a report can
        // legitimately count accounts it does not list. Reading the lists instead of the counts would
        // silently under-report exactly the busiest case this exists for
        $reconciliation = new PatreonBenefitReconciliation(
            holderCount: 400,
            unmatchedCount: 120,
            blockedCount: 0,
            downgradedCount: 0,
            unmatchedHolders: [],
            blockedHolders: [],
        );

        // Act
        $needsAttention = $reconciliation->needsAttention();

        // Assert
        $this->assertTrue($needsAttention);
    }
}
