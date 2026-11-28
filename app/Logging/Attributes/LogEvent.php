<?php

namespace App\Logging\Attributes;

use Attribute;
use Monolog\Level;

/**
 * Declares one log event of an EventStructuredLogging interface and the level it is written at. An event whose
 * name ends in Start or End opens or closes a start()/end() group instead, and ignores the level.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final readonly class LogEvent
{
    public function __construct(
        public string $name,
        public Level  $level = Level::Info,
    ) {
    }
}
