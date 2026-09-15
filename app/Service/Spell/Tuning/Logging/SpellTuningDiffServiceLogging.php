<?php

namespace App\Service\Spell\Tuning\Logging;

use App\Logging\Concerns\InteractsWithRollbar;
use App\Logging\StructuredLogging;

class SpellTuningDiffServiceLogging extends StructuredLogging implements SpellTuningDiffServiceLoggingInterface
{
    use InteractsWithRollbar;

    public function diffStart(string $fromBuild, string $toBuild, int $gameVersionId): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    public function diffSpellChanged(int $spellId, int $changeCount): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function diffEnd(): void
    {
        $this->end(__METHOD__);
    }

    public function storeStart(string $fromBuild, string $toBuild, int $gameVersionId, int $changeCount): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    public function storeEnd(): void
    {
        $this->end(__METHOD__);
    }
}
