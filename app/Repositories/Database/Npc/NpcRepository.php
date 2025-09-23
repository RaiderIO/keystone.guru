<?php

namespace App\Repositories\Database\Npc;

use App\Models\Dungeon;
use App\Models\Mapping\MappingVersion;
use App\Models\Npc\Npc;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\Npc\NpcRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class NpcRepository extends DatabaseRepository implements NpcRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(Npc::class);
    }

    public function getInUseNpcs(MappingVersion $mappingVersion): Collection
    {
        $mappingVersion->load('dungeon');

        return Npc::select('npcs.*')
            ->leftJoin('npc_enemy_forces', 'npcs.id', 'npc_enemy_forces.npc_id')
            ->join('npc_dungeons', 'npc_dungeons.npc_id', '=', 'npcs.id')
            ->where(function (Builder $builder) use ($mappingVersion) {
                $builder
                    ->where('npc_dungeons.dungeon_id', $mappingVersion->dungeon_id)
                    ->where(function (Builder $builder) use ($mappingVersion) {
                        // Enemy forces may be not set, that means that we assume 0. They MAY be missing entirely for bosses
                        // or for other exceptions listed below
                        $builder->where(
                            'npc_enemy_forces.mapping_version_id',
                            $mappingVersion->id,
                        )->orWhereNull('npc_enemy_forces.id');
                    });
            })
            ->when($mappingVersion->dungeon->key === Dungeon::DUNGEON_NELTHARIONS_LAIR, function (Builder $builder) {
                $builder->orWhereIn('npcs.id', [
                    // Burning Geodes are in the mapping but give 0 enemy forces.
                    // They're in the mapping because they're dangerous af
                    101437,
                ]);
            })
            ->when($mappingVersion->dungeon->key === Dungeon::DUNGEON_THE_NECROTIC_WAKE, function (Builder $builder) {
                $builder->orWhereIn('npcs.id', [
                    // Necrotic Wake:
                    // Brittlebone Warrior is in the mapping but gives 0 enemy forces.
                    163122,
                    // Brittlebone Mage
                    163126,
                    // Brittlebone Crossbowman
                    166079,
                    // Spare Parts
                    166264,
                    // Goregrind Bits
                    163622,
                    // Rotspew Leftovers
                    163623,
                ]);
            })
            ->when($mappingVersion->dungeon->key === Dungeon::DUNGEON_HALLS_OF_INFUSION, function (Builder $builder) {
                $builder->orWhereIn('npcs.id', [
                    // Aqua Ragers are in the mapping but give 0 enemy forces - so would be excluded.
                    // They're in the mapping because they are a significant drain on time and excluding them would raise questions about why they're gone
                    190407,
                ]);
            })
            ->when($mappingVersion->dungeon->key === Dungeon::DUNGEON_BRACKENHIDE_HOLLOW, function (Builder $builder) {
                $builder->orWhereIn('npcs.id', [
                    // Witherlings that are a significant nuisance to be included in the mapping. They give 0 enemy forces.
                    194273,
                    // Rotfang Hyena are part of Gutshot boss but, they are part of the mapping. They give 0 enemy forces.
                    194745,
                    // Wild Lashers give 0 enemy forces but are in the mapping regardless
                    191243,
                    // Wither Slashers give 0 enemy forces but are in the mapping regardless
                    194469,
                    // Gutstabbers give 0 enemy forces but are in the mapping regardless
                    197857,
                ]);
            })
            ->when($mappingVersion->dungeon->key === Dungeon::DUNGEON_THE_NOKHUD_OFFENSIVE, function (
                Builder $builder,
            ) {
                $builder->orWhereIn('npcs.id', [
                    // War Ohuna gives 0 enemy forces but is in the mapping regardless
                    192803,
                    // Stormsurge Totem gives 0 enemy forces but is in the mapping regardless
                    194897,
                    // Unstable Squall gives 0 enemy forces but is in the mapping regardless
                    194895,
                    // Primal Gust gives 0 enemy forces but is in the mapping regardless
                    195579,
                ]);
            })
            ->when(in_array($mappingVersion->dungeon->key, [
                Dungeon::DUNGEON_DAWN_OF_THE_INFINITE_GALAKRONDS_FALL,
                Dungeon::DUNGEON_DAWN_OF_THE_INFINITE_MUROZONDS_RISE,
            ]), function (Builder $builder) {
                $builder->orWhereIn('npcs.id', [
                    // Temporal Deviation gives 0 enemy forces but is in the mapping regardless
                    206063,
                    // Iridikron's Creation
                    204918,
                ]);
            })
            ->when($mappingVersion->dungeon->key === Dungeon::DUNGEON_CITY_OF_THREADS, function (Builder $builder) {
                $builder->orWhereIn('npcs.id', [
                    // Eye of the Queen gives 0 enemy forces but is in the mapping regardless
                    220003,
                ]);
            })
            ->get();
    }

    public function getInUseNpcIds(MappingVersion $mappingVersion, ?Collection $inUseNpcs = null): Collection
    {
        return ($inUseNpcs ?? $this->getInUseNpcs($mappingVersion))
            ->pluck('id')
            // Brackenhide Hollow:  Odd exception to make Brackenhide Gnolls show up. They aren't in the MDT mapping, so
            // they don't get npc_enemy_forces pushed. But we do need them to show up for us since they convert
            // into Witherlings which ARE on the mapping. Without this exception, they wouldn't turn up and the
            // Witherlings would never get mapped properly
            ->push(194373);
    }
}
