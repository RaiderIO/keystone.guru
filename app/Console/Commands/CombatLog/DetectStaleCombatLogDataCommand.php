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
use Symfony\Component\Console\Helper\ProgressBar;

class DetectStaleCombatLogDataCommand extends Command
{
    protected $signature = 'combatlog:detectstaledata';

    protected $description = 'Removes stale NPC characteristics and spell properties that have no recent observation, and prunes old observation rows.';

    public function handle(): int
    {
        $this->removeStaleNpcCharacteristics();
        $this->removeStaleSpellProperties();
        $this->pruneOldObservations();

        return self::SUCCESS;
    }

    private function observationWindowDays(): int
    {
        return config('keystoneguru.combat_log_staleness.observation_window_days');
    }

    private function removeStaleNpcCharacteristics(): void
    {
        $cutoff = now()->subDays($this->observationWindowDays())->toDateString();
        $total  = NpcCharacteristic::query()->count();

        $this->info(sprintf('Scanning %d NPC characteristics for stale data...', $total));

        $bar = $this->output->createProgressBar($total);
        $bar->setFormat(ProgressBar::FORMAT_NORMAL);
        $bar->start();

        $removedCount = 0;

        NpcCharacteristic::query()->chunk(200, function (Collection $chunk) use ($cutoff, $bar, &$removedCount): void {
            /** @var Collection<CombatLogNpcCharacteristicObservation> $chunk */
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
                    $removedCount++;

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

            $bar->advance($chunk->count());
        });

        $bar->finish();
        $this->newLine();
        $this->info(sprintf('Removed %d stale NPC characteristic(s).', $removedCount));
        $this->newLine();
    }

    private function removeStaleSpellProperties(): void
    {
        $cutoff       = now()->subDays($this->observationWindowDays())->toDateString();
        $removedCount = 0;

        $this->info('Scanning spell properties for stale data...');

        foreach (SpellProperty::cases() as $property) {
            $removedCount += $this->removeStaleSpellProperty($property, $cutoff);
        }

        $this->info(sprintf('Removed %d stale spell property assignment(s).', $removedCount));
        $this->newLine();
    }

    private function removeStaleSpellProperty(SpellProperty $property, string $cutoff): int
    {
        $query = Spell::query();

        match ($property) {
            SpellProperty::Aura   => $query->where('aura', true),
            SpellProperty::Debuff => $query->where('debuff', true),
            default               => $query->whereRaw(sprintf('miss_types_mask & %d != 0', $this->getMissTypeBit($property))),
        };

        $total        = (clone $query)->count();
        $removedCount = 0;

        $bar = $this->output->createProgressBar($total);
        $bar->setFormat(sprintf(' %%current%%/%%max%% [%%bar%%] %%percent:3s%%%% — %s', $property->value));
        $bar->start();

        $query->chunk(200, function (Collection $spells) use ($property, $cutoff, $bar, &$removedCount): void {
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
                    $removedCount++;

                    CombatLogSpellEvent::create([
                        'spell_id'        => $spell->id,
                        'event_type'      => CombatLogSpellEventType::PropertyRemoved,
                        'property'        => $property,
                        'combat_log_path' => null,
                    ]);
                }
            }

            $bar->advance($spells->count());
        });

        $bar->finish();
        $this->newLine();

        return $removedCount;
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
        $pruneDate = now()->subDays($this->observationWindowDays() + 1)->toDateString();

        $this->info(sprintf('Pruning observations older than %d days...', $this->observationWindowDays() + 1));

        $npcCount   = CombatLogNpcCharacteristicObservation::query()->where('observed_on', '<', $pruneDate)->delete();
        $spellCount = CombatLogSpellPropertyObservation::query()->where('observed_on', '<', $pruneDate)->delete();

        $this->info(sprintf(
            'Pruned %d NPC characteristic observation(s) and %d spell property observation(s).',
            $npcCount,
            $spellCount,
        ));
    }
}
