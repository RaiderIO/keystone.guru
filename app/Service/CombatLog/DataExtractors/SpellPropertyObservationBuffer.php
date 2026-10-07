<?php

namespace App\Service\CombatLog\DataExtractors;

use App\Models\CombatLog\CombatLogSpellPropertyObservation;
use App\Models\CombatLog\SpellProperty;
use Illuminate\Support\Carbon;

/**
 * Collects the (spell_id, property) observations of one extraction pass so every writer of
 * combat_log_spell_property_observations shares a single upsert per combat log.
 */
class SpellPropertyObservationBuffer
{
    /** @var array<string, array{spell_id: int, property: SpellProperty, combat_log_path: string}> */
    private array $pendingObservations = [];

    public function queue(int $spellId, SpellProperty $property, string $combatLogPath): void
    {
        $this->pendingObservations[sprintf('%d-%s', $spellId, $property->value)] ??= [
            'spell_id'        => $spellId,
            'property'        => $property,
            'combat_log_path' => $combatLogPath,
        ];
    }

    /**
     * @return array<string, array{spell_id: int, property: SpellProperty, combat_log_path: string}>
     */
    public function getPendingObservations(): array
    {
        return $this->pendingObservations;
    }

    /**
     * @return int The number of observation rows written.
     */
    public function flush(): int
    {
        if (empty($this->pendingObservations)) {
            return 0;
        }

        $now        = Carbon::now()->toDateTimeString();
        $observedOn = Carbon::today()->toDateString();

        $rows = array_map(static fn(array $observation) => [
            'spell_id'        => $observation['spell_id'],
            'property'        => $observation['property']->value,
            'observed_on'     => $observedOn,
            'combat_log_path' => $observation['combat_log_path'],
            'created_at'      => $now,
            'updated_at'      => $now,
        ], array_values($this->pendingObservations));

        $this->pendingObservations = [];

        CombatLogSpellPropertyObservation::upsertWithDeadlockRetry(
            $rows,
            ['spell_id', 'property', 'observed_on'],
            ['combat_log_path', 'updated_at'],
        );

        return count($rows);
    }
}
