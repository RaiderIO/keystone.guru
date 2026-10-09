<?php

namespace App\Service\CombatLog\DataExtractors;

use App\Models\CombatLog\CombatLogSpellPropertyObservation;
use App\Models\CombatLog\SpellProperty;
use Closure;
use Illuminate\Support\Carbon;

/**
 * Collects the (spell_id, property) observations of one extraction pass so every writer of
 * combat_log_spell_property_observations shares a single upsert per combat log.
 */
class SpellPropertyObservationBuffer
{
    /** @var array<string, array{spell_id: int, property: SpellProperty, combat_log_path: string}> */
    private array $pendingObservations = [];

    /** @var list<Closure(): void> */
    private array $afterFlushCallbacks = [];

    public function queue(int $spellId, SpellProperty $property, string $combatLogPath): void
    {
        $this->pendingObservations[sprintf('%d-%s', $spellId, $property->value)] ??= [
            'spell_id'        => $spellId,
            'property'        => $property,
            'combat_log_path' => $combatLogPath,
        ];
    }

    /**
     * Registers work that may only run once the queued observations are written - applying a property before its
     * observation exists lets the staleness sweep clear it in between.
     *
     * @param Closure(): void $callback
     */
    public function afterFlush(Closure $callback): void
    {
        $this->afterFlushCallbacks[] = $callback;
    }

    /**
     * Writes every queued observation in one upsert, then runs the registered after-flush callbacks in order.
     *
     * @return int The number of observation rows written.
     */
    public function flush(): int
    {
        $rows = $this->buildRows();

        $this->pendingObservations = [];

        if (!empty($rows)) {
            CombatLogSpellPropertyObservation::upsertWithDeadlockRetry(
                $rows,
                ['spell_id', 'property', 'observed_on'],
                ['combat_log_path', 'updated_at'],
            );
        }

        $callbacks                 = $this->afterFlushCallbacks;
        $this->afterFlushCallbacks = [];
        foreach ($callbacks as $callback) {
            $callback();
        }

        return count($rows);
    }

    /**
     * @return list<array{spell_id: int, property: string, observed_on: string, combat_log_path: string, created_at: string, updated_at: string}>
     */
    private function buildRows(): array
    {
        $now        = Carbon::now()->toDateTimeString();
        $observedOn = Carbon::today()->toDateString();

        return array_map(static fn(array $observation) => [
            'spell_id'        => $observation['spell_id'],
            'property'        => $observation['property']->value,
            'observed_on'     => $observedOn,
            'combat_log_path' => $observation['combat_log_path'],
            'created_at'      => $now,
            'updated_at'      => $now,
        ], array_values($this->pendingObservations));
    }
}
