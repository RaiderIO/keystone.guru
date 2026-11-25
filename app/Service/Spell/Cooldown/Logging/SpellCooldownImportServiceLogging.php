<?php

namespace App\Service\Spell\Cooldown\Logging;

use App\Logging\StructuredLogging;

class SpellCooldownImportServiceLogging extends StructuredLogging implements SpellCooldownImportServiceLoggingInterface
{
    public function importCooldownsStart(string $product, string $build, int $gameVersionId): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    public function importCooldownsEnd(): void
    {
        $this->end(__METHOD__);
    }

    public function importCooldownsUnknownBuild(string $product): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    public function importCooldownsNoSpellsFound(string $product, string $build): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    public function importSpecializationsTableUnavailable(string $build, string $message): void
    {
        $this->warning(__METHOD__, get_defined_vars());
    }

    public function fillGapsFromWowheadNoCooldown(int $spellId): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function fillGapsFromWowheadFilled(int $spellId, int $cooldownMs, ?int $charges): void
    {
        $this->info(__METHOD__, get_defined_vars());
    }
}
