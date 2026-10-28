<?php

namespace App\Service\CombatLog;

use App\Models\CombatLog\CombatLogNpcEvent;
use App\Models\CombatLog\CombatLogSpellEvent;
use App\Models\CombatLog\SpellProperty;
use Illuminate\Support\Carbon;

/**
 * Writes the compendium activity events for facts gained and lost, but only when the fact's observation history makes
 * the change meaningful. The fact itself is written or expired by the caller either way.
 */
interface CombatLogActivityEventServiceInterface
{
    /**
     * Call after the spell gained the property and today's observation was written.
     *
     * @return CombatLogSpellEvent|null the event, or null when the change was not meaningful enough to report
     */
    public function recordSpellPropertyAdded(int $spellId, SpellProperty $property, ?string $combatLogPath): ?CombatLogSpellEvent;

    /**
     * Call when the staleness sweep expired the property.
     *
     * @param Carbon $stalenessCutoff the first data-day of the staleness window
     */
    public function recordSpellPropertyRemoved(int $spellId, SpellProperty $property, Carbon $stalenessCutoff): ?CombatLogSpellEvent;

    /**
     * Call after the NPC gained the characteristic and today's observation was written.
     */
    public function recordNpcCharacteristicAdded(int $npcId, int $characteristicId, ?string $combatLogPath): ?CombatLogNpcEvent;

    /**
     * Call when the staleness sweep expired the characteristic.
     *
     * @param Carbon $stalenessCutoff the first data-day of the staleness window
     */
    public function recordNpcCharacteristicRemoved(int $npcId, int $characteristicId, Carbon $stalenessCutoff): ?CombatLogNpcEvent;
}
