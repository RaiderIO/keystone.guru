<?php

namespace App\Mcp\Support;

use App\Logic\Structs\LatLng;
use App\Models\Enemy;
use App\Models\Mapping\MappingVersion;
use App\Models\Npc\Npc;
use App\Models\Npc\NpcClassification;
use App\Models\Npc\NpcEnemyForces;
use App\Service\Coordinates\CoordinatesServiceInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

/**
 * The enemies of one mapping version, with their NPCs and enemy forces loaded once, formatted for MCP tool output in
 * in-game coordinates (yards) - never the map's lat/lng. MDT placeholders are left out: the route editor hides them
 * and they cannot be pulled.
 */
class MappingVersionEnemies
{
    /** @var Collection<int, NpcEnemyForces> npc id => enemy forces */
    private readonly Collection $npcEnemyForces;

    /** @var EloquentCollection<int, Enemy> enemy id => enemy */
    private readonly EloquentCollection $enemies;

    public function __construct(
        private readonly CoordinatesServiceInterface $coordinatesService,
        private readonly MappingVersion              $mappingVersion,
    ) {
        $this->enemies = $mappingVersion->enemies()
            ->where(static fn(Builder $builder) => $builder->whereNull('seasonal_type')
                ->orWhereNot('seasonal_type', Enemy::SEASONAL_TYPE_MDT_PLACEHOLDER))
            ->with(['floor', 'npc.classification', 'npc.type'])
            ->get()
            ->keyBy('id');

        $this->npcEnemyForces = NpcEnemyForces::query()
            ->where('mapping_version_id', $mappingVersion->id)
            ->get()
            ->keyBy('npc_id');
    }

    public function getMappingVersion(): MappingVersion
    {
        return $this->mappingVersion;
    }

    /**
     * @return EloquentCollection<int, Enemy> enemy id => enemy
     */
    public function getEnemies(): EloquentCollection
    {
        return $this->enemies;
    }

    public function getEnemyForces(Enemy $enemy, bool $teeming = false): int
    {
        if ($teeming) {
            return $enemy->enemy_forces_override_teeming ??
                $this->npcEnemyForces->get($enemy->npc_id)->enemy_forces_teeming ??
                $this->getEnemyForces($enemy);
        }

        return $enemy->enemy_forces_override ?? $this->npcEnemyForces->get($enemy->npc_id)->enemy_forces ?? 0;
    }

    public function isBoss(Enemy $enemy): bool
    {
        return $enemy->npc !== null && in_array($enemy->npc->classification_id, [
            NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_BOSS],
            NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_FINAL_BOSS],
        ], true);
    }

    /**
     * An enemy as the tools return it. Flags that are off, fields that are unset and teeming enemy forces equal to
     * the regular enemy forces are left out to keep the payload of a whole dungeon compact.
     *
     * @return array<string, mixed>
     */
    public function formatEnemy(Enemy $enemy): array
    {
        $ingameXY = $this->coordinatesService->calculateIngameLocationForMapLocation(
            new LatLng($enemy->lat, $enemy->lng, $enemy->floor),
        );

        $enemyForces        = $this->getEnemyForces($enemy);
        $enemyForcesTeeming = $this->getEnemyForces($enemy, true);

        return array_filter([
            'id'                   => $enemy->id,
            'npc_id'               => $enemy->npc_id,
            'floor_id'             => $enemy->floor_id,
            'x'                    => round($ingameXY->getX(), 1),
            'y'                    => round($ingameXY->getY(), 1),
            'enemy_pack_id'        => $enemy->enemy_pack_id,
            'enemy_patrol_id'      => $enemy->enemy_patrol_id,
            'enemy_forces'         => $enemyForces,
            'enemy_forces_teeming' => $enemyForcesTeeming === $enemyForces ? null : $enemyForcesTeeming,
            'seasonal_type'        => $enemy->seasonal_type,
            'seasonal_index'       => $enemy->seasonal_index,
            'teeming'              => $enemy->teeming,
            'faction'              => $enemy->faction === 'any' ? null : $enemy->faction,
            'required'             => $enemy->required ?: null,
            'skippable'            => $enemy->skippable ?: null,
            'hyper_respawn'        => $enemy->hyper_respawn ?: null,
            'dungeon_difficulty'   => $enemy->dungeon_difficulty,
            'exclusive_enemy_id'   => $enemy->exclusive_enemy_id,
        ], static fn($value) => $value !== null && $value !== '');
    }

    /**
     * Every NPC that occurs in the mapping version, keyed by NPC id - so enemies only carry the id.
     *
     * @param  array<int, int|null>|null        $npcIds only these NPCs; null for all of them
     * @return array<int, array<string, mixed>>
     */
    public function formatNpcs(?array $npcIds = null): array
    {
        return $this->enemies
            ->pluck('npc')
            ->filter()
            ->when($npcIds !== null, static fn(Collection $npcs) => $npcs->whereIn('id', $npcIds))
            ->unique('id')
            ->sortBy('id')
            ->mapWithKeys(fn(Npc $npc) => [
                $npc->id => array_filter([
                    'name'                 => __($npc->name),
                    'classification'       => $npc->classification?->key,
                    'type'                 => $npc->type->type,
                    'enemy_forces'         => $this->npcEnemyForces->get($npc->id)?->enemy_forces,
                    'enemy_forces_teeming' => $this->npcEnemyForces->get($npc->id)?->enemy_forces_teeming,
                    'dangerous'            => $npc->dangerous ?: null,
                    'truesight'            => $npc->truesight ?: null,
                    'bursting'             => $npc->bursting ?: null,
                    'bolstering'           => $npc->bolstering ?: null,
                    'sanguine'             => $npc->sanguine ?: null,
                    'runs_away_in_fear'    => $npc->runs_away_in_fear ?: null,
                ], static fn($value) => $value !== null),
            ])
            ->all();
    }
}
