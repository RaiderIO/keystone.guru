<?php

namespace App\Service\CombatLog\Filters\Logging;

use App\Logging\RollbarStructuredLogging;

class BaseCombatFilterLogging extends RollbarStructuredLogging implements BaseCombatFilterLoggingInterface
{
    public function parseUnitDied(int $lineNr, string $guid): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function parseUnitDiedEnemyWasNotPartOfCurrentPull(int $lineNr, string $guid): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function parseUnitDiedEnemyWasAlreadyKilled(int $lineNr, string $guid): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function parseUnitDiedEnemyWasSummoned(int $lineNr, string $guid): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function parseUnitDiedInvalidNpcId(int $lineNr, string $guid): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function parseUnitDiedEnemyWasNotEngaged(int $lineNr, string $guid): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function parseUnitInCurrentPullKilled(int $lineNr, string $guid): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function parseUnitSummonedInWhitelist(int $lineNr, string $guid): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function parseUnitSummoned(int $lineNr, string $guid): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function parseUnitFirstSighted(int $lineNr, string $guid): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function parseUnitEvadedRemovedFromCurrentPull(int $lineNr, string $guid): void
    {
        $this->info(__METHOD__, get_defined_vars());
    }

    public function parseUnitAddedToCurrentPull(int $lineNr, string $newEnemyGuid): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function getEnemyEngagedEventUsingFirstSightedEvent(string $guid): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function getEnemyEngagedEventUsingEngagedEvent(string $guid): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }
}
