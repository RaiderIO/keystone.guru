<?php

namespace App\Service\Spell\Cooldown\Logging;

interface SpellCooldownImportServiceLoggingInterface
{
    public function importCooldownsStart(string $product, string $build, int $gameVersionId): void;

    public function importCooldownsEnd(): void;

    public function importCooldownsUnknownBuild(string $product): void;

    public function importCooldownsNoSpellsFound(string $product, string $build): void;

    public function importSpecializationsTableUnavailable(string $build, string $message): void;

    public function fillGapsFromWowheadNoCooldown(int $spellId): void;

    public function fillGapsFromWowheadFilled(int $spellId, int $cooldownMs, ?int $charges): void;
}
