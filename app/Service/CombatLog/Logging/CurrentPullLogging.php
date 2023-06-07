<?php

namespace App\Service\CombatLog\Logging;

use App\Logging\StructuredLogging;

class CurrentPullLogging extends StructuredLogging implements CurrentPullLoggingInterface
{
    /**
     * @return void
     */
    public function parseChallengeModeStarted(): void
    {
        $this->info(__METHOD__);
    }
    
    /**
     * @return void
     */
    public function parseChallengeModeEnded(): void
    {
        $this->info(__METHOD__);
    }
    
    /**
     * @param string $destGuid
     *
     * @return void
     */
    public function parseUnitDied(string $destGuid): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }
    
    /**
     * @param string $guid
     *
     * @return void
     */
    public function parseUnitInCurrentPullKilled(string $guid): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    /**
     * @param string $guid
     *
     * @return void
     */
    public function parseUnitSummoned(string $guid): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }
    
    /**
     * @param string $guid
     *
     * @return void
     */
    public function parseUnitEvadedRemovedFromCurrentPull(string $guid): void
    {
        $this->info(__METHOD__, get_defined_vars());
    }
    
    /**
     * @param string $newEnemyGuid
     *
     * @return void
     */
    public function parseUnitAddedToCurrentPull(string $newEnemyGuid): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }
}