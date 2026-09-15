<?php

namespace App\Service\Spell\Tuning\Logging;

interface SpellTuningDiffServiceLoggingInterface
{
    public function diffStart(string $fromBuild, string $toBuild, int $gameVersionId): void;

    public function diffSpellChanged(int $spellId, int $changeCount): void;

    public function diffEnd(): void;

    public function storeStart(string $fromBuild, string $toBuild, int $gameVersionId, int $changeCount): void;

    public function storeEnd(): void;
}
