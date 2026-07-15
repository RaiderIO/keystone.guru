<?php

namespace App\Service\MDT;

use Carbon\CarbonInterface;

/**
 * Resolves MDT addon version numbers (the `addonVersion` integer embedded in MDT export strings,
 * e.g. 6115 for MDT v6.1.15) to their upstream GitHub release dates.
 *
 * The addonVersion integer is MDT's own `tonumber(version:gsub("%.", ""))` encoding and is NOT
 * orderable across MDT's historical version schemes, so it is only ever used as a lookup key into
 * the release-date map; all ordering/comparison must happen on the resolved dates.
 */
interface MDTAddonVersionServiceInterface
{
    /**
     * Resolve an MDT addonVersion integer to the date its upstream release was published, or null
     * when the version is unknown (e.g. newer than what has been synced, or a value with no release).
     */
    public function getReleaseDate(int $addonVersion): ?CarbonInterface;

    /**
     * The MDT addonVersion whose release most recently precedes (or equals) the given date — i.e. the
     * MDT version that was live at that moment. Null when the date predates every known release. Used to
     * backfill the imported-from version of mapping versions created before this data was tracked.
     */
    public function getAddonVersionForDate(CarbonInterface $date): ?int;

    /**
     * The MDT addonVersion integer the application currently ships, derived from
     * `config('keystoneguru.mdt.version')` (e.g. "v6.1.20" => 6120).
     */
    public function getCurrentAddonVersion(): int;
}
