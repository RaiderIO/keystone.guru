<?php

namespace App\Service\CombatLog\Logging;

use App\Logging\StructuredLogging;

class CurrentPullLogging extends StructuredLogging implements CurrentPullLoggingInterface
{
    /**
     * @param int $lineNr
     *
     * @return void
     */
    public function parseChallengeModeStarted(int $lineNr): void
    {
        $this->info(__METHOD__);
    }

    /**
     * @param int $lineNr
     *
     * @return void
     */
    public function parseChallengeModeEnded(int $lineNr): void
    {
        $this->info(__METHOD__);
    }

    /**
     * @param int    $lineNr
     * @param string $guid
     *
     * @return void
     */
    public function parseUnitDied(int $lineNr, string $guid): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }
    
    /**
     * @param int    $lineNr
     * @param string $guid
     *
     * @return void
     */
    public function parseUnitDiedEnemyWasNotPartOfCurrentPull(int $lineNr, string $guid): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }
    
    /**
     * @param int    $lineNr
     * @param string $guid
     *
     * @return void
     */
    public function parseUnitDiedEnemyWasAlreadyKilled(int $lineNr, string $guid): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }
    
    /**
     * @param int    $lineNr
     * @param string $guid
     *
     * @return void
     */
    public function parseUnitDiedEnemyWasSummoned(int $lineNr, string $guid): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    /**
     * @param int    $lineNr
     * @param string $guid
     *
     * @return void
     */
    public function parseUnitDiedInvalidNpcId(int $lineNr, string $guid): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    /**
     * @param int    $lineNr
     * @param string $guid
     *
     * @return void
     */
    public function parseUnitInCurrentPullKilled(int $lineNr, string $guid): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    /**
     * @param int    $lineNr
     * @param string $guid
     *
     * @return void
     */
    public function parseUnitSummoned(int $lineNr, string $guid): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    /**
     * @param int    $lineNr
     * @param string $guid
     *
     * @return void
     */
    public function parseUnitFirstSighted(int $lineNr, string $guid): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    /**
     * @param int    $lineNr
     * @param string $guid
     *
     * @return void
     */
    public function parseUnitEvadedRemovedFromCurrentPull(int $lineNr, string $guid): void
    {
        $this->info(__METHOD__, get_defined_vars());
    }

    /**
     * @param int    $lineNr
     * @param string $newEnemyGuid
     *
     * @return void
     */
    public function parseUnitAddedToCurrentPull(int $lineNr, string $newEnemyGuid): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }
    
    /**
     * @param string $guid
     *
     * @return void
     */
    public function getEnemyEngagedEventUsingFirstSightedEvent(string $guid): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    /**
     * @param string $guid
     *
     * @return void
     */
    public function getEnemyEngagedEventUsingEngagedEvent(string $guid): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }
}