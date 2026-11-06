<?php

namespace Tests\Unit\App\Logging;

use App\Logging\Attributes\LogEvent;
use App\Logging\EventStructuredLoggingInterface;
use Monolog\Level;

/**
 * @method void somethingDebugged(int $itemId)
 * @method void somethingNoticed(int $itemId)
 * @method void somethingInformed(int $itemId)
 * @method void somethingWarned(int $itemId)
 * @method void somethingBroke(int $itemId, string $exceptionClass)
 * @method void somethingCritical(int $itemId)
 * @method void somethingAlerted(int $itemId)
 * @method void somethingEmergency(int $itemId)
 * @method void jobStart(string $jobName)
 * @method void jobEnd()
 */
#[LogEvent('somethingDebugged', Level::Debug)]
#[LogEvent('somethingNoticed', Level::Notice)]
#[LogEvent('somethingInformed', Level::Info)]
#[LogEvent('somethingWarned', Level::Warning)]
#[LogEvent('somethingBroke', Level::Error)]
#[LogEvent('somethingCritical', Level::Critical)]
#[LogEvent('somethingAlerted', Level::Alert)]
#[LogEvent('somethingEmergency', Level::Emergency)]
#[LogEvent('jobStart')]
#[LogEvent('jobEnd')]
interface TestableEventLoggingInterface extends EventStructuredLoggingInterface
{
}
