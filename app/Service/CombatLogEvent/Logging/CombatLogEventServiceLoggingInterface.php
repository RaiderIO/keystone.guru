<?php

namespace App\Service\CombatLogEvent\Logging;

use Exception;

interface CombatLogEventServiceLoggingInterface
{

    public function getCombatLogEventsStart(array $filters): void;

    public function getCombatLogEventsException(Exception $e): void;

    public function getCombatLogEventsEnd(): void;
}
