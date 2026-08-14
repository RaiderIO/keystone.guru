<?php

namespace App\Service\Spell\Description;

use Closure;

/**
 * Measures what a spell's damage and healing coefficients are multiplied by to become the amounts the
 * game shows.
 *
 * Nothing in the game client's own data derives it: the spell carries no content tuning, and only a
 * handful of creatures carry one of their own. It is measured against the game's rendered numbers
 * instead, once per patch, and shipped in the seeders - never fetched while serving a page.
 */
interface SpellDamageCalibrationServiceInterface
{
    /**
     * @param bool                         $force      remeasure spells that already have a multiplier
     * @param Closure(int, int): void|null $onProgress called with the number of spells done and the total
     *
     * @return array{measured: int, unchanged: int, disagreed: int, unreadable: int}
     */
    public function calibrate(bool $force = false, ?int $spellId = null, ?Closure $onProgress = null): array;
}
