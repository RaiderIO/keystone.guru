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
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use LogicException;

class DetectStaleCombatLogDataCommand extends Command
{
    protected $signature = 'combatlog:detectstaledata';

    protected $description = 'Removes stale NPC characteristics and spell properties that have no recent observation, and prunes old observation rows.';

    /** @var Collection<int, int>|null */
    private ?Collection $currentSeasonDungeonIds = null;

    /**
     * Distinct `observed_on` dates (as 'Y-m-d' strings) present across both observation tables,
     * sorted descending. Index 0 is the most recent data-day, index 1 the one before it, etc.
     *
     * @var Collection<int, string>|null
     */
    private ?Collection $dataDates = null;

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

    /**
     * @return Collection<int, int>|null
     */
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

    /**
     * @return Collection<int, string>
     */
    private function getDataDates(): Collection
    {
        if ($this->dataDates !== null) {
            return $this->dataDates;
        }

        $npcDates = CombatLogNpcCharacteristicObservation::query()
            ->distinct()
            ->pluck('observed_on')
            ->map(fn(Carbon $date) => $date->toDateString());

        $spellDates = CombatLogSpellPropertyObservation::query()
            ->distinct()
            ->pluck('observed_on')
            ->map(fn(Carbon $date) => $date->toDateString());

        return $this->dataDates = $npcDates->merge($spellDates)
            ->unique()
            ->sortDesc()
            ->values();
    }

    /**
     * The date beyond which a fact with no fresher observation is considered stale, or null when
     * there isn't yet enough observation history to say anything is stale.
     */
    private function getStalenessCutoff(): ?string
    {
        return $this->getDataDates()->get($this->observationWindowDays());
    }

    /**
     * The date beyond which observation rows are pruned, or null when there isn't yet enough
     * observation history to prune anything.
     */
    private function getPruneCutoff(): ?string
    {
        return $this->getDataDates()->get($this->observationWindowDays() + 1);
    }

    private function removeStaleNpcCharacteristics(): void
    {
        $currentSeasonDungeonIds = $this->getCurrentSeasonDungeonIds();
        if ($currentSeasonDungeonIds === null) {
            $this->warn('No current season found - skipping NPC characteristic stale detection.');

            return;
        }

        $cutoff = $this->getStalenessCutoff();
        if ($cutoff === null) {
            $this->info('combatlog:detectstaledata — not enough observation history yet - skipping NPC characteristic stale detection.');

            return;
        }

        $total = NpcCharacteristic::query()
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
                    // toBase() drops to the plain query builder for this bulk delete, skipping Eloquent's
                    // caching builder layer.
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

        $cutoff = $this->getStalenessCutoff();
        if ($cutoff === null) {
            $this->info('combatlog:detectstaledata — not enough observation history yet - skipping spell property stale detection.');

            return;
        }

        $removedCount = 0;

        foreach (SpellProperty::cases() as $property) {
            $removedCount += $this->removeStaleSpellProperty($property, $cutoff, $currentSeasonDungeonIds);
        }

        $this->info(sprintf('combatlog:detectstaledata — spell_properties total_removed=%d', $removedCount));
    }

    /**
     * @param Collection<int, int> $currentSeasonDungeonIds
     */
    private function removeStaleSpellProperty(SpellProperty $property, string $cutoff, Collection $currentSeasonDungeonIds): int
    {
        $query = Spell::query()
            ->disableCache()
            ->whereIn('id', function ($q) use ($currentSeasonDungeonIds): void {
                $q->select('spell_id')->from('spell_dungeons')->whereIn('dungeon_id', $currentSeasonDungeonIds);
            });

        match (true) {
            $property === SpellProperty::Aura   => $query->where('aura', true),
            $property === SpellProperty::Debuff => $query->where('debuff', true),
            $property->isCounter()              => $query->whereRaw(sprintf('counters_mask & %d != 0', $this->getCounterBit($property))),
            $property->isImmunityBypass()       => $query->whereRaw(sprintf('bypasses_immunities_mask & %d != 0', $this->getImmunityBit($property))),
            default                             => $query->whereRaw(sprintf('miss_types_mask & %d != 0', $this->getMissTypeBit($property))),
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
        } elseif ($property->isCounter()) {
            $spell->counters_mask &= ~$this->getCounterBit($property);
        } elseif ($property->isImmunityBypass()) {
            $spell->bypasses_immunities_mask &= ~$this->getImmunityBit($property);
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

    private function getCounterBit(SpellProperty $property): int
    {
        foreach (Spell::ALL_COUNTERS as $bit => $name) {
            if ($property->value === sprintf('counter_%s', $name)) {
                return $bit;
            }
        }

        throw new LogicException(sprintf('No counter bit found for SpellProperty: %s', $property->value));
    }

    private function getImmunityBit(SpellProperty $property): int
    {
        foreach (Spell::ALL_IMMUNITIES as $bit => $name) {
            if ($property->value === sprintf('bypass_%s', $name)) {
                return $bit;
            }
        }

        throw new LogicException(sprintf('No immunity bit found for SpellProperty: %s', $property->value));
    }

    private function pruneOldObservations(): void
    {
        $pruneDate = $this->getPruneCutoff();
        if ($pruneDate === null) {
            $this->info('combatlog:detectstaledata — not enough observation history yet - skipping observation pruning.');

            return;
        }

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
