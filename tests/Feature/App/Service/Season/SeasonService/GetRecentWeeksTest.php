<?php

namespace Tests\Feature\App\Service\Season\SeasonService;

use App\Models\GameServerRegion;
use App\Service\Season\Dtos\SeasonWeek;
use App\Service\Season\SeasonServiceInterface;
use Illuminate\Support\Carbon;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('SeasonService')]
#[Group('GetRecentWeeks')]
final class GetRecentWeeksTest extends PublicTestCase
{
    #[Override]
    protected function tearDown(): void
    {
        try {
            Carbon::setTestNow();
        } finally {
            parent::tearDown();
        }
    }

    /**
     * The US epoch is Tuesday 2005-12-27 15:00 UTC, so its weekly reset is Tuesday 15:00 UTC.
     */
    #[Test]
    #[DataProvider('nowProvider')]
    public function getRecentWeeks_givenWeekCount_returnsThatManyWeeksEndingAtTheCurrentPeriod(
        string $now,
        int    $expectedCurrentPeriod,
        string $expectedCurrentWeekStart,
    ): void {
        // Arrange
        Carbon::setTestNow(Carbon::parse($now, 'UTC'));
        $usRegion = GameServerRegion::where('key', GameServerRegion::AMERICAS)->firstOrFail();

        // Act
        $result = app(SeasonServiceInterface::class)->getRecentWeeks($usRegion, 3);

        // Assert
        $this->assertSame([1, 2, 3], $result->keys()->all());
        $this->assertSame([1, 2, 3], $result->map(static fn(SeasonWeek $seasonWeek) => $seasonWeek->week)->values()->all());
        $this->assertSame(
            [$expectedCurrentPeriod - 2, $expectedCurrentPeriod - 1, $expectedCurrentPeriod],
            $result->map(static fn(SeasonWeek $seasonWeek) => $seasonWeek->period)->values()->all(),
        );
        $this->assertSame($expectedCurrentWeekStart, $result->last()->start->toDateTimeString());
        $this->assertSame(
            Carbon::parse($expectedCurrentWeekStart, 'UTC')->subWeeks(2)->toDateTimeString(),
            $result->first()->start->toDateTimeString(),
        );
    }

    /**
     * @return array<string, array{0: string, 1: int, 2: string}>
     */
    public static function nowProvider(): array
    {
        return [
            'just before the reset' => ['2026-11-10 12:00:00', 1088, '2026-11-03 15:00:00'],
            'just after the reset'  => ['2026-11-10 16:00:00', 1089, '2026-11-10 15:00:00'],
        ];
    }
}
