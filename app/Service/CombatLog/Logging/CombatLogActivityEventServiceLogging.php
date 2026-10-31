<?php

namespace App\Service\CombatLog\Logging;

use App\Logging\Concerns\InteractsWithRollbar;
use App\Logging\StructuredLogging;

class CombatLogActivityEventServiceLogging extends StructuredLogging implements CombatLogActivityEventServiceLoggingInterface
{
    use InteractsWithRollbar;

    public function addSuppressed(string $subjectType, int $subjectId, string $fact, int $factDays, int $subjectDays): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function removalSuppressed(string $subjectType, int $subjectId, string $fact, int $factDays, int $subjectDays, int $recentSubjectDays): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }
}
