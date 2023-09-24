<?php

namespace App\Service\CombatLog\Logging;

use App\Logging\StructuredLogging;

class CombatLogServiceLogging extends StructuredLogging implements CombatLogServiceLoggingInterface
{

    /**
     * @param string $rawEvent
     *
     * @return void
     */
    public function parseCombatLogToEventsUnableToParseRawEvent(string $rawEvent): void
    {
        $this->warning(__METHOD__, get_defined_vars());
    }

    /**
     * @param string $combatLogFilePath
     *
     * @return void
     */
    public function getResultEventsStart(string $combatLogFilePath): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    /**
     * @return void
     */
    public function getResultEventsEnd(): void
    {
        $this->end(__METHOD__);
    }

    /**
     * @return void
     */
    public function extractCombatLogExtractingArchiveStart(): void
    {
        $this->start(__METHOD__);
    }

    /**
     * @return void
     */
    public function extractCombatLogInvalidZipFile(): void
    {
        $this->error(__METHOD__);
    }

    /**
     * @param string $extractedFilePath
     *
     * @return void
     */
    public function extractCombatLogExtractedArchive(string $extractedFilePath): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    /**
     * @return void
     */
    public function extractCombatLogExtractingArchiveEnd(): void
    {
        $this->end(__METHOD__);
    }

    /**
     * @return void
     */
    public function parseCombatLogParseEventsStart(): void
    {
        $this->start(__METHOD__);
    }

    /**
     * @param int $combatLogVersion
     * @return void
     */
    public function parseCombatLogParseEventsChangedCombatLogVersion(int $combatLogVersion): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    /**
     * @return void
     */
    public function parseCombatLogParseEventsEnd(): void
    {
        $this->end(__METHOD__);
    }

    /**
     * @return void
     */
    public function compressCombatLogCompressingArchiveStart(): void
    {
        $this->start(__METHOD__);
    }

    /**
     * @return void
     */
    public function compressCombatLogInvalidZipFile(): void
    {
        $this->error(__METHOD__);
    }

    /**
     * @param string $targetFilePath
     * @return void
     */
    public function compressCombatLogCompressedArchive(string $targetFilePath): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    /**
     * @return void
     */
    public function compressCombatLogCompressingArchiveEnd(): void
    {
        $this->end(__METHOD__);
    }


}
