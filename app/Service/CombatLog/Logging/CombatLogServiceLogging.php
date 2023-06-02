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
     * @return void
     */
    public function extractCombatLogExtractingArchive(): void
    {
        $this->debug(__METHOD__);
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
    public function parseCombatLogParseEventsStart(): void
    {
        $this->start(__METHOD__);
    }

    /**
     * @return void
     */
    public function parseCombatLogParseEventsEnd(): void
    {
        $this->end(__METHOD__);
    }
}
