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
use App\Service\Season\SeasonServiceInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use LogicException;

class DetectStaleCombatLogDataCommand extends Command
{
    protected $signature = 'combatlog:detectstaledata';

    protected $description = 'Removes stale NPC characteristics and spell properties that have no recent observation, and prunes old observation rows.';

    private ?Collection $currentSeasonDungeonIds = null;

    public function __construct(private readonly SeasonServiceInterface $seasonService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info(sprintf('combatlog:detectstaledata — window=%dd', $this->observationWindowDays()));

        $this->removeStaleNpcCharacteristics();
        $this->removeStaleSpellProperties();
        $this->pruneOldObservations();

        return self::SUCCESS;
    }

    private function observationWindowDays(): int
    {
        return config('keystoneguru.combat_log_staleness.observation_window_days');
    }

    private function getCurrentSeasonDungeonIds(): ?Collection
    {
        if ($this->currentSeasonDungeonIds !== null) {
            return $this->currentSeasonDungeonIds;
        }

        $currentSeason = $this->seasonService->getCurrentSeason();
        if ($currentSeason === null) {
            return null;
        }

        return $this->currentSeasonDungeonIds = $currentSeason->seasonDungeons()->pluck('dungeon_id');
    }

    private function removeStaleNpcCharacteristics(): void
    {
        $currentSeasonDungeonIds = $this->getCurrentSeasonDungeonIds();
        if ($currentSeasonDungeonIds === null) {
            $this->warn('No current season found - skipping NPC characteristic stale detection.');

            return;
        }

        $cutoff = now()->subDays($this->observationWindowDays())->toDateString();
        $total  = NpcCharacteristic::query()
            ->disableCache()
            ->whereIn('npc_id', function ($q) use ($currentSeasonDungeonIds): void {
                $q->select('npc_id')->from('npc_dungeons')->whereIn('dungeon_id', $currentSeasonDungeonIds);
            })
            ->count();

        $removedCount = 0;

        NpcCharacteristic::query()
            ->disableCache()
            ->whereIn('npc_id', function ($q) use ($currentSeasonDungeonIds): void {
                $q->select('npc_id')->from('npc_dungeons')->whereIn('dungeon_id', $currentSeasonDungeonIds);
            })
            ->chunkById(200, function (Collection $chunk) use ($cutoff, &$removedCount): void {
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
            });

        $this->info(sprintf('combatlog:detectstaledata — npc_characteristics scanned=%d removed=%d', $total, $removedCount));
    }

    private function removeStaleSpellProperties(): void
    {
        $currentSeasonDungeonIds = $this->getCurrentSeasonDungeonIds();
        if ($currentSeasonDungeonIds === null) {
            $this->warn('No current season found - skipping spell property stale detection.');

            return;
        }

        $cutoff       = now()->subDays($this->observationWindowDays())->toDateString();
        $removedCount = 0;

        foreach (SpellProperty::cases() as $property) {
            $removedCount += $this->removeStaleSpellProperty($property, $cutoff, $currentSeasonDungeonIds);
        }

        $this->info(sprintf('combatlog:detectstaledata — spell_properties total_removed=%d', $removedCount));
    }

    private function removeStaleSpellProperty(SpellProperty $property, string $cutoff, Collection $currentSeasonDungeonIds): int
    {
        $query = Spell::query()
            ->disableCache()
            ->whereIn('id', function ($q) use ($currentSeasonDungeonIds): void {
                $q->select('spell_id')->from('spell_dungeons')->whereIn('dungeon_id', $currentSeasonDungeonIds);
            });

        match ($property) {
            SpellProperty::Aura   => $query->where('aura', true),
            SpellProperty::Debuff => $query->where('debuff', true),
            default               => $query->whereRaw(sprintf('miss_types_mask & %d != 0', $this->getMissTypeBit($property))),
        };

        $total        = (clone $query)->count();
        $removedCount = 0;

        $query->chunkById(200, function (Collection $spells) use ($property, $cutoff, &$removedCount): void {
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
        });

        $this->info(sprintf('combatlog:detectstaledata — spell_property=%s scanned=%d removed=%d', $property->value, $total, $removedCount));

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

        throw new LogicException(sprintf('No miss type bit found for SpellProperty: %s', $property->value));
    }

    private function pruneOldObservations(): void
    {
        $pruneDate = now()->subDays($this->observationWindowDays() + 1)->toDateString();

        $npcCount   = CombatLogNpcCharacteristicObservation::query()->where('observed_on', '<', $pruneDate)->delete();
        $spellCount = CombatLogSpellPropertyObservation::query()->where('observed_on', '<', $pruneDate)->delete();

        $this->info(sprintf(
            'combatlog:detectstaledata — pruned npc_observations=%d spell_observations=%d cutoff=%s',
            $npcCount,
            $spellCount,
            $pruneDate,
        ));
    }
}
