<?php

namespace App\Mcp\Tools\GameData;

use App\Mcp\Exceptions\McpToolException;
use App\Mcp\Support\MappingVersionEnemies;
use App\Mcp\Tools\McpTool;
use App\Models\Enemy;
use App\Models\EnemyPack;
use App\Models\User;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\User\ApiTokenScope;
use App\Service\WalkGraph\PackDistanceServiceInterface;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description(<<<'TEXT'
    Lists every pack of a dungeon's current mapping with its enemies, plus the enemies that belong to no pack
    (patrols, lone mobs). Coordinates are in-game yards per floor. Each enemy references an NPC in the npcs map
    (name, classification, enemy forces); an enemy lists enemy_forces_teeming only where it differs. Each pack lists
    its nearest packs on the same floor with the distance in yards: the walking distance where players' movement has
    been measured, otherwise the straight line, marked unverified (or, when no distance of the dungeon has been
    measured, distances_measured is false). Enemies with a seasonal_type, teeming or dungeon_difficulty only appear
    under those conditions. For a large dungeon, ask for one floor at a time.
    TEXT)]
#[IsReadOnly]
#[IsIdempotent]
class GetDungeonPacksTool extends McpTool
{
    protected string $name = 'get_dungeon_packs';

    public function __construct(
        private readonly CoordinatesServiceInterface  $coordinatesService,
        private readonly PackDistanceServiceInterface $packDistanceService,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'dungeon' => $schema->string()
                ->description('The dungeon key or slug, from list_dungeons.')
                ->required(),
            'floor' => $schema->integer()
                ->description('Only list the packs and enemies of this floor id, from list_dungeons. Defaults to every floor.'),
        ];
    }

    protected function requiredScope(): ApiTokenScope
    {
        return ApiTokenScope::RoutesRead;
    }

    protected function run(Request $request, User $user): Response|ResponseFactory
    {
        $validated = $request->validate([
            'dungeon' => ['required', 'string', 'max:255'],
            'floor'   => ['nullable', 'integer'],
        ]);

        $dungeon        = $this->findActiveDungeon($validated['dungeon']);
        $mappingVersion = $dungeon->getCurrentMappingVersion();
        if ($mappingVersion === null) {
            throw new McpToolException(__('mcp.error.dungeon_not_found', ['dungeon' => $validated['dungeon']]));
        }

        $floorId = isset($validated['floor']) ? (int)$validated['floor'] : null;
        if ($floorId !== null && !$dungeon->floors()->whereKey($floorId)->exists()) {
            throw new McpToolException(__('mcp.error.unknown_dungeon_floor', ['floor' => $floorId]));
        }

        $mappingVersionEnemies = new MappingVersionEnemies($this->coordinatesService, $mappingVersion);
        $enemies               = $mappingVersionEnemies->getEnemies();
        if ($floorId !== null) {
            $enemies = $enemies->where('floor_id', $floorId);
        }

        // Packs of other floors keep no enemies and so get no centroid, which leaves them out
        $enemyPacks = $mappingVersion->enemyPacks()->with('floor')->get();
        foreach ($enemyPacks as $enemyPack) {
            $enemyPack->setRelation('enemies', $enemies->where('enemy_pack_id', $enemyPack->id)->values());
        }

        $centroids  = $this->packDistanceService->getPackCentroids($enemyPacks);
        $neighbours = $this->packDistanceService->getNeighbours(
            $mappingVersion,
            $enemyPacks,
            (int)config('keystoneguru.api.mcp.pack_neighbour_count'),
        );

        $distancesMeasured = collect($neighbours)->flatten(1)->contains(static fn(array $neighbour) => !$neighbour['unverified']);

        return Response::structured([
            'dungeon' => [
                'key'                           => $dungeon->key,
                'name'                          => __($dungeon->name),
                'mapping_version'               => $mappingVersion->version,
                'enemy_forces_required'         => $mappingVersion->enemy_forces_required,
                'enemy_forces_required_teeming' => $mappingVersion->enemy_forces_required_teeming,
                'timer_max_seconds'             => $mappingVersion->timer_max_seconds,
                'distances_measured'            => $distancesMeasured,
            ],
            'npcs'  => $mappingVersionEnemies->formatNpcs($enemies->pluck('npc_id')->unique()->all()),
            'packs' => $enemyPacks
                ->filter(static fn(EnemyPack $enemyPack) => isset($centroids[$enemyPack->id]))
                ->sortBy('id')
                ->map(fn(EnemyPack $enemyPack) => array_filter([
                    'id'           => $enemyPack->id,
                    'floor_id'     => $enemyPack->floor_id,
                    'group'        => $enemyPack->group,
                    'x'            => round($centroids[$enemyPack->id]->getX(), 1),
                    'y'            => round($centroids[$enemyPack->id]->getY(), 1),
                    'teeming'      => $enemyPack->teeming,
                    'faction'      => $enemyPack->faction === 'any' ? null : $enemyPack->faction,
                    'enemy_forces' => $enemyPack->enemies->sum(
                        static fn(Enemy $enemy) => $mappingVersionEnemies->getEnemyForces($enemy),
                    ),
                    // The pack already carries the floor and its own id
                    'enemies' => $enemyPack->enemies
                        ->map(static fn(Enemy $enemy) => array_diff_key(
                            $mappingVersionEnemies->formatEnemy($enemy),
                            ['floor_id' => true, 'enemy_pack_id' => true],
                        ))
                        ->values()
                        ->all(),
                    'neighbours' => array_map(static fn(array $neighbour) => array_filter([
                        'id'         => $neighbour['enemy_pack_id'],
                        'distance'   => $neighbour['distance'],
                        'unverified' => $distancesMeasured && $neighbour['unverified'] ? true : null,
                    ], static fn($value) => $value !== null), $neighbours[$enemyPack->id] ?? []),
                ], static fn($value) => $value !== null && $value !== ''))
                ->values()
                ->all(),
            'loose_enemies' => $enemies
                ->whereNull('enemy_pack_id')
                ->map($mappingVersionEnemies->formatEnemy(...))
                ->values()
                ->all(),
        ]);
    }
}
