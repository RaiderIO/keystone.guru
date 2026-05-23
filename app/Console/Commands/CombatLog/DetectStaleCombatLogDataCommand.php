<?php

namespace App\Console\Commands\CombatLog;

use App\Models\Characteristic;
use App\Models\CombatLog\CombatLogNpcCharacteristicObservation;
use App\Models\CombatLog\CombatLogNpcEvent;
use App\Models\CombatLog\CombatLogNpcEventType;
use App\Models\CombatLog\CombatLogSpellEvent;
use App\Models\CombatLog\CombatLogSpellEventType;
use App\Models\CombatLog\CombatLogSpellPropertyObservation;
use App\Models\CombatLog\SpellProperty;
use App\Models\Npc\NpcCharacteristic;
use App\Models\Spell\Spell;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class DetectStaleCombatLogDataCommand extends Command
{
    public const int OBSERVATION_WINDOW_DAYS = 3;

    protected $signature = 'combatlog:detectstaledata';

    protected $description = 'Removes stale NPC characteristics and spell properties that have no recent observation, and prunes old observation rows.';

    public function handle(): int
    {
        $this->removeStaleNpcCharacteristics();
        $this->removeStaleSpellProperties();
        $this->pruneOldObservations();

        return self::SUCCESS;
    }

    private function removeStaleNpcCharacteristics(): void
    {
        $cutoff = now()->subDays(self::OBSERVATION_WINDOW_DAYS)->toDateString();

        NpcCharacteristic::query()->chunk(200, function (Collection $chunk) use ($cutoff): void {
            $freshKeys = CombatLogNpcCharacteristicObservation::query()
                ->select(['npc_id', 'characteristic_id'])
                ->whereIn('npc_id', $chunk->pluck('npc_id')->unique())
                ->where('observed_on', '>=', $cutoff)
                ->get()
                ->map(fn(CombatLogNpcCharacteristicObservation $obs) => sprintf('%d_%d', $obs->npc_id, $obs->characteristic_id))
                ->flip()
                ->all();

            $staleIds = [];

            foreach ($chunk as $npcCharacteristic) {
                $key = sprintf('%d_%d', $npcCharacteristic->npc_id, $npcCharacteristic->characteristic_id);
                if (!array_key_exists($key, $freshKeys)) {
                    $staleIds[] = $npcCharacteristic->getKey();

                    CombatLogNpcEvent::create([
                        'npc_id'          => $npcCharacteristic->npc_id,
                        'event_type'      => CombatLogNpcEventType::CharacteristicRemoved,
                        'model_class'     => Characteristic::class,
                        'model_id'        => $npcCharacteristic->characteristic_id,
                        'combat_log_path' => null,
                    ]);
                }
            }

            if (!empty($staleIds)) {
                // Use toBase() to bypass SeederModel's deleting observer, which only allows admin users to delete
                NpcCharacteristic::query()->whereIn('id', $staleIds)->toBase()->delete();
            }
        });
    }

    private function removeStaleSpellProperties(): void
    {
        $cutoff = now()->subDays(self::OBSERVATION_WINDOW_DAYS)->toDateString();

        foreach (SpellProperty::cases() as $property) {
            $this->removeStaleSpellProperty($property, $cutoff);
        }
    }

    private function removeStaleSpellProperty(SpellProperty $property, string $cutoff): void
    {
        $query = Spell::query();

        match ($property) {
            SpellProperty::Aura   => $query->where('aura', true),
            SpellProperty::Debuff => $query->where('debuff', true),
            default               => $query->whereRaw(sprintf('miss_types_mask & %d != 0', $this->getMissTypeBit($property))),
        };

        $query->chunk(200, function (Collection $spells) use ($property, $cutoff): void {
            $freshSpellIds = CombatLogSpellPropertyObservation::query()
                ->whereIn('spell_id', $spells->pluck('id'))
                ->where('property', $property)
                ->where('observed_on', '>=', $cutoff)
                ->pluck('spell_id')
                ->flip()
                ->all();

            foreach ($spells as $spell) {
                if (!array_key_exists($spell->id, $freshSpellIds)) {
                    $this->clearSpellProperty($spell, $property);

                    CombatLogSpellEvent::create([
                        'spell_id'        => $spell->id,
                        'event_type'      => CombatLogSpellEventType::PropertyRemoved,
                        'property'        => $property,
                        'combat_log_path' => null,
                    ]);
                }
            }
        });
    }

    private function clearSpellProperty(Spell $spell, SpellProperty $property): void
    {
        if ($property === SpellProperty::Aura) {
            $spell->aura = false;
        } elseif ($property === SpellProperty::Debuff) {
            $spell->debuff = false;
        } else {
            $spell->miss_types_mask &= ~$this->getMissTypeBit($property);
        }

        $spell->save();
    }

    private function getMissTypeBit(SpellProperty $property): int
    {
        foreach (Spell::ALL_MISS_TYPES as $bit => $name) {
            if ($property->value === sprintf('miss_%s', $name)) {
                return $bit;
            }
        }

        throw new \LogicException(sprintf('No miss type bit found for SpellProperty: %s', $property->value));
    }

    private function pruneOldObservations(): void
    {
        $pruneDate = now()->subDays(self::OBSERVATION_WINDOW_DAYS + 1)->toDateString();

        CombatLogNpcCharacteristicObservation::query()->where('observed_on', '<', $pruneDate)->delete();
        CombatLogSpellPropertyObservation::query()->where('observed_on', '<', $pruneDate)->delete();
    }
}
