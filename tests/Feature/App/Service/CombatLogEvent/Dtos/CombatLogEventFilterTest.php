<?php

namespace Tests\Feature\App\Service\CombatLogEvent\Dtos;

use App;
use App\Models\CombatLog\CombatLogEventDataType;
use App\Models\CombatLog\CombatLogEventEventType;
use App\Service\CombatLogEvent\Dtos\CombatLogEventFilter;
use App\Service\RaiderIO\Dtos\HeatmapDataFilter;
use App\Service\Season\SeasonAffixGroupServiceInterface;
use App\Service\Season\SeasonServiceInterface;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\ProvidesDungeon;
use Tests\TestCases\PublicTestCase;

#[Group('CombatLogEvent')]
final class CombatLogEventFilterTest extends PublicTestCase
{
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
        [$dungeon, $mappingVersion] = $this->findDungeon();

        $originalTimerMaxSeconds           = $mappingVersion->timer_max_seconds;
        $mappingVersion->timer_max_seconds = 0;
        $mappingVersion->save();

        try {
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
                App::make(SeasonAffixGroupServiceInterface::class),
                $heatmapDataFilter,
            );
        } finally {
            $mappingVersion->timer_max_seconds = $originalTimerMaxSeconds;
            $mappingVersion->save();
        }
    }
}
