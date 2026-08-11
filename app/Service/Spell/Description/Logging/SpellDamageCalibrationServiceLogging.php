<?php

namespace App\Service\Spell\Description\Logging;

use App\Logging\Concerns\InteractsWithRollbar;
use App\Logging\StructuredLogging;

class SpellDamageCalibrationServiceLogging extends StructuredLogging implements SpellDamageCalibrationServiceLoggingInterface
{
    use InteractsWithRollbar;

    public function calibrateStart(int $spellCount): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    public function calibrateEnd(): void
    {
        $this->end(__METHOD__);
    }

    public function calibrateDisagreed(int $spellId, array $candidates): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function calibrateMisaligned(int $spellId, float $ours, float $theirs): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }
}
