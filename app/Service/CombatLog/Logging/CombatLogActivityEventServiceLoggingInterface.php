<?php

namespace App\Service\CombatLog\Logging;

interface CombatLogActivityEventServiceLoggingInterface
{
    public function addSuppressed(string $subjectType, int $subjectId, string $fact, int $factDays, int $subjectDays): void;

    public function removalSuppressed(string $subjectType, int $subjectId, string $fact, int $factDays, int $subjectDays, int $recentSubjectDays): void;
}
