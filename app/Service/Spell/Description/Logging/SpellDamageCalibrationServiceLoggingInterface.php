<?php

namespace App\Service\Spell\Description\Logging;

interface SpellDamageCalibrationServiceLoggingInterface
{
    public function calibrateStart(int $spellCount): void;

    public function calibrateEnd(): void;

    /** @param array<int, float> $candidates */
    public function calibrateDisagreed(int $spellId, array $candidates): void;
}
