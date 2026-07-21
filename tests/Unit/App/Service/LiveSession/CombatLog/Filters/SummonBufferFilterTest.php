<?php

namespace Tests\Unit\App\Service\LiveSession\CombatLog\Filters;

use App\Logic\CombatLog\BaseEvent;
use App\Logic\CombatLog\CombatEvents\CombatLogEvent;
use App\Logic\CombatLog\CombatEvents\Suffixes\Suffix;
use App\Logic\CombatLog\CombatEvents\Suffixes\Summon;
use App\Service\LiveSession\CombatLog\Filters\SummonBufferFilter;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('LiveSession')]
#[Group('SummonBufferFilter')]
final class SummonBufferFilterTest extends PublicTestCase
{
    #[Test]
    public function shouldKeep_givenSummonEvent_returnsTrue(): void
    {
        // Arrange
        $filter = new SummonBufferFilter();
        $event  = $this->createMock(CombatLogEvent::class);
        $event->method('getSuffix')->willReturn($this->createMock(Summon::class));

        // Act & Assert
        $this->assertTrue($filter->shouldKeep($event));
    }

    #[Test]
    public function shouldKeep_givenNonSummonSuffix_returnsFalse(): void
    {
        // Arrange
        $filter = new SummonBufferFilter();
        $event  = $this->createMock(CombatLogEvent::class);
        $event->method('getSuffix')->willReturn($this->createMock(Suffix::class));

        // Act & Assert
        $this->assertFalse($filter->shouldKeep($event));
    }

    #[Test]
    public function shouldKeep_givenNonCombatLogEvent_returnsFalse(): void
    {
        // Arrange
        $filter = new SummonBufferFilter();
        $event  = $this->createMock(BaseEvent::class);

        // Act & Assert
        $this->assertFalse($filter->shouldKeep($event));
    }
}
