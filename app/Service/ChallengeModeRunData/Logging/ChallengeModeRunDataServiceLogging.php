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

    public function convertChallengeModeRunDataStart(int $challengeModeRunDataId): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    public function convertChallengeModeRunDataNoChallengeModeIdSet(): void
    {
        $this->debug(__METHOD__);
    }

    public function convertChallengeModeRunDataEnd(int $count): void
    {
        $this->end(__METHOD__, get_defined_vars());
    }


}
