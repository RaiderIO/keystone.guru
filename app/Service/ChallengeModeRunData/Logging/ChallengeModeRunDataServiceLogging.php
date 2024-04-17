<?php

namespace App\Service\ChallengeModeRunData\Logging;

use App\Logging\StructuredLogging;

class ChallengeModeRunDataServiceLogging extends StructuredLogging implements ChallengeModeRunDataServiceLoggingInterface
{
    public function convertStart(): void
    {
        $this->start(__METHOD__);
    }

    public function convertEnd(): void
    {
        $this->end(__METHOD__);
    }

    public function convertChallengeModeRunDataStart(): void
    {
        $this->start(__METHOD__);
    }

    public function convertChallengeModeRunDataUnableToFindDungeon(int $mapId): void
    {
        $this->warning(__METHOD__, get_defined_vars());
    }

    public function convertChallengeModeRunDefaultAttributes(array $defaultAttributes): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function convertChallengeModeRunDataEnd(int $count): void
    {
        $this->end(__METHOD__, get_defined_vars());
    }

}
