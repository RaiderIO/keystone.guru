<?php

namespace App\Jobs\Logging;

use App\Logging\StructuredLogging;

class RegenerateCombatLogRouteLogging extends StructuredLogging implements RegenerateCombatLogRouteLoggingInterface
{
    public function handleStart(int $dungeonRouteId): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    public function handleDungeonRouteNotFound(): void
    {
        $this->error(__METHOD__);
    }

    public function handleChallengeModeRunNotSet(): void
    {
        $this->error(__METHOD__);
    }

    public function handleSuccess(): void
    {
        $this->info(__METHOD__, get_defined_vars());
    }

    public function handleRequestError(string $message): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    public function handleEnd(bool $result): void
    {
        $this->end(__METHOD__, get_defined_vars());
    }
}
