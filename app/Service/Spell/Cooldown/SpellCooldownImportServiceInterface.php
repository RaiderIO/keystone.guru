<?php

namespace App\Service\Spell\Cooldown;

use App\Service\Spell\Cooldown\Dtos\SpellCooldownImportResult;
use App\Service\WagoTools\Exceptions\WagoToolsDownloadException;

interface SpellCooldownImportServiceInterface
{
    /**
     * Store the base cooldown, charges and specializations of every spell of a game version, from the DB2 tables of
     * one game build. Spells that build does not know are left alone; with `$fillGapsFromWowhead`, the player spells
     * among them get their cooldown from Wowhead instead.
     *
     * Returns null when the build could not be determined, or its tables say nothing about any spell we know.
     *
     * @param string      $product the CDN product to read DB2 data for, e.g. `wow`
     * @param string|null $build   a specific build; the most recent one when null
     *
     * @throws WagoToolsDownloadException
     */
    public function importCooldowns(
        string  $product,
        int     $gameVersionId,
        ?string $build = null,
        bool    $fillGapsFromWowhead = true,
    ): ?SpellCooldownImportResult;
}
