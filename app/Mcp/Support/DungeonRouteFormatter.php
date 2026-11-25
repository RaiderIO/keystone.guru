<?php

namespace App\Mcp\Support;

use App\Logic\Structs\LatLng;
use App\Models\Affix;
use App\Models\AffixGroup\AffixGroup;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Faction;
use App\Models\KillZone\KillZone;
use App\Models\PublishedState;
use App\Models\Spell\Spell;
use App\Service\Coordinates\CoordinatesServiceInterface;

/**
 * Formats one of the caller's own routes for MCP tool output. Kill areas are returned in in-game coordinates.
 */
readonly class DungeonRouteFormatter
{
    public function __construct(
        private CoordinatesServiceInterface $coordinatesService,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function formatSummary(DungeonRoute $dungeonRoute): array
    {
        return [
            'public_key'      => $dungeonRoute->public_key,
            'title'           => $dungeonRoute->title,
            'dungeon'         => $dungeonRoute->dungeon->key,
            'published_state' => array_search($dungeonRoute->published_state_id, PublishedState::ALL, true) ?: null,
            'ai_source'       => $dungeonRoute->ai_source?->value,
            'url'             => route('dungeonroute.view', [
                'dungeon'      => $dungeonRoute->dungeon,
                'dungeonroute' => $dungeonRoute,
                'title'        => $dungeonRoute->getTitleSlug(),
            ]),
            'updated_at' => $dungeonRoute->updated_at->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function formatRoute(DungeonRoute $dungeonRoute): array
    {
        $dungeonRoute->load([
            'dungeon',
            'mappingVersion',
            'affixes.affixes',
            'routeattributesraw',
            'playerclasses',
            'playerspecializations',
            'playerraces',
            'killZones.killZoneEnemies',
            'killZones.spells',
            'killZones.floor',
        ]);

        $mappingVersion = $dungeonRoute->mappingVersion;

        return array_merge($this->formatSummary($dungeonRoute), [
            'description'                => $dungeonRoute->description,
            'mapping_version'            => $mappingVersion->version,
            'mapping_version_is_current' => $dungeonRoute->dungeon->getCurrentMappingVersion()?->id === $mappingVersion->id,
            'level_min'                  => $dungeonRoute->level_min,
            'level_max'                  => $dungeonRoute->level_max,
            'teeming'                    => $dungeonRoute->teeming,
            'seasonal_index'             => $dungeonRoute->seasonal_index,
            'faction'                    => array_search($dungeonRoute->faction_id, Faction::ALL, true) ?: null,
            'affix_groups'               => $dungeonRoute->affixes->map(static fn(AffixGroup $affixGroup) => [
                'id'      => $affixGroup->id,
                'affixes' => $affixGroup->affixes->map(static fn(Affix $affix) => $affix->key)->values()->all(),
            ])->values()->all(),
            'attribute_ids' => $dungeonRoute->routeattributesraw->pluck('route_attribute_id')->map(static fn($id) => (int)$id)->values()->all(),
            'party'         => [
                'class_ids'          => $dungeonRoute->playerclasses->pluck('character_class_id')->map(static fn($id) => (int)$id)->values()->all(),
                'specialization_ids' => $dungeonRoute->playerspecializations->pluck('character_class_specialization_id')->map(static fn($id) => (int)$id)->values()->all(),
                'race_ids'           => $dungeonRoute->playerraces->pluck('character_race_id')->map(static fn($id) => (int)$id)->values()->all(),
            ],
            'dungeon_start_id'              => $dungeonRoute->dungeon_start_id,
            'dungeon_difficulty'            => $dungeonRoute->dungeon_difficulty,
            'pull_gradient'                 => $dungeonRoute->pull_gradient,
            'pull_gradient_apply_always'    => $dungeonRoute->pull_gradient_apply_always,
            'enemy_forces'                  => $dungeonRoute->getEnemyForces(),
            'enemy_forces_required'         => $mappingVersion->enemy_forces_required,
            'enemy_forces_required_teeming' => $mappingVersion->enemy_forces_required_teeming,
            'pulls'                         => $dungeonRoute->killZones->map($this->formatPull(...))->values()->all(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function formatPull(KillZone $killZone): array
    {
        $killArea = null;
        if ($killZone->hasKillArea()) {
            $ingameXY = $this->coordinatesService->calculateIngameLocationForMapLocation(
                new LatLng($killZone->lat, $killZone->lng, $killZone->floor),
            );

            $killArea = [
                'floor_id' => $killZone->floor_id,
                'x'        => round($ingameXY->getX(), 1),
                'y'        => round($ingameXY->getY(), 1),
            ];
        }

        return [
            'id'          => $killZone->id,
            'index'       => $killZone->index,
            'color'       => $killZone->color,
            'description' => $killZone->description,
            'enemy_ids'   => $killZone->killZoneEnemies->pluck('enemy_id')->map(static fn($id) => (int)$id)->sort()->values()->all(),
            'spells'      => $killZone->spells->map(static fn(Spell $spell) => [
                'id'   => $spell->id,
                'name' => __($spell->name ?? ''),
            ])->values()->all(),
            'kill_area' => $killArea,
        ];
    }
}
