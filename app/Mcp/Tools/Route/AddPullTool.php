<?php

namespace App\Mcp\Tools\Route;

use App\Features\WalkGraphAggroReachability;
use App\Http\Requests\KillZone\APIKillZoneFormRequest;
use App\Logic\Structs\IngameXY;
use App\Logic\Structs\LatLng;
use App\Mcp\Exceptions\McpToolException;
use App\Mcp\Support\DungeonRouteFormatter;
use App\Mcp\Tools\McpTool;
use App\Mcp\Tools\Route\Concerns\ResolvesPullEnemies;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Enemy;
use App\Models\EnemyPack;
use App\Models\Faction;
use App\Models\KillZone\KillZone;
use App\Models\User;
use App\Repositories\Interfaces\DungeonRepositoryInterface;
use App\Repositories\Interfaces\DungeonRoute\DungeonRouteRepositoryInterface;
use App\Repositories\Interfaces\KillZone\KillZoneEnemyRepositoryInterface;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\KillZone\KillZoneServiceInterface;
use App\Service\User\ApiTokenScope;
use App\Service\WalkGraph\PackReachabilityServiceInterface;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Pennant\Feature;

#[Description('Adds a pull to the end of one of your routes, killing every enemy of the given packs plus the given individual enemies (ids from get_dungeon_packs). pack_ids is a shorthand for all enemies of a pack; to kill only part of a pack, list those enemies in enemy_ids instead. The pull is stored and returned as its enemies only. An enemy can only be in one pull: enemies already pulled elsewhere are refused. Returns the new pull and the route\'s enemy forces, and where aggro_aware is on, reachable_packs: the nearest packs on the pull\'s floor that can be walked to from it without coming within aggro range of any enemy or patrol the route has not killed yet, with the detour_ratio of a walk around them.')]
class AddPullTool extends McpTool
{
    use ResolvesPullEnemies;

    protected string $name = 'add_pull';

    public function __construct(
        private readonly KillZoneServiceInterface         $killZoneService,
        private readonly DungeonRouteFormatter            $dungeonRouteFormatter,
        private readonly KillZoneEnemyRepositoryInterface $killZoneEnemyRepository,
        private readonly PackReachabilityServiceInterface $packReachabilityService,
        private readonly CoordinatesServiceInterface      $coordinatesService,
        DungeonRouteRepositoryInterface                   $dungeonRouteRepository,
        DungeonRepositoryInterface                        $dungeonRepository,
    ) {
        parent::__construct($dungeonRouteRepository, $dungeonRepository);
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'route'       => $schema->string()->description('The route\'s public_key.')->required(),
            'pack_ids'    => $schema->array()->items($schema->integer())->description('Packs whose enemies are all killed in this pull.'),
            'enemy_ids'   => $schema->array()->items($schema->integer())->description('Individual enemies killed in this pull.'),
            'color'       => $schema->string()->pattern('^#([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$')->description('The pull\'s color, e.g. #ff0000. When left out it is picked as the route editor does: light after a dark pull, dark after a light one.'),
            'description' => $schema->string()->max(KillZone::DESCRIPTION_MAX_LENGTH)->description('A note on the pull, shown in the route\'s pull list.'),
        ];
    }

    protected function requiredScope(): ApiTokenScope
    {
        return ApiTokenScope::RoutesWrite;
    }

    protected function run(Request $request, User $user): Response|ResponseFactory
    {
        $validated = $request->validate([
            'route'       => ['required', 'string', 'max:255'],
            'pack_ids'    => ['nullable', 'array', 'max:100'],
            'pack_ids.*'  => ['integer'],
            'enemy_ids'   => ['nullable', 'array', 'max:200'],
            'enemy_ids.*' => ['integer'],
            'color'       => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
        ]);

        $dungeonRoute = $this->findOwnDungeonRoute($user, $validated['route'], 'edit');

        $packIds  = array_map('intval', $validated['pack_ids'] ?? []);
        $enemyIds = array_map('intval', $validated['enemy_ids'] ?? []);
        if ($packIds === [] && $enemyIds === []) {
            throw new McpToolException(__('mcp.error.no_enemies_given'));
        }

        $enemyIds = $this->getEnemyIdsOfPacksAndEnemies($dungeonRoute, $packIds, $enemyIds);

        $this->consumeRateLimit($user, 'edit_pull', (int)config('keystoneguru.api.mcp.edit_pull_per_hour'));

        $killZone = DB::transaction(function () use ($dungeonRoute, $user, $validated, $enemyIds): KillZone {
            $this->lockDungeonRoute($dungeonRoute);

            $this->ensureNotPulledYet($dungeonRoute, $enemyIds);

            $killZoneData = $this->validateWithFormRequest(APIKillZoneFormRequest::class, $user, array_filter([
                'color'       => $validated['color'] ?? nextPullColor($dungeonRoute->killZones()->orderByDesc('index')->value('color')),
                'description' => $validated['description'] ?? null,
                'index'       => (int)$dungeonRoute->killZones()->max('index') + 1,
                'enemies'     => $enemyIds,
                'spells'      => [],
            ], static fn($value) => $value !== null));
            $killZoneData['spells'] ??= [];
            $killZoneData['id'] = null;

            return $this->killZoneService->saveKillZone($dungeonRoute, $killZoneData);
        }, 3);
        $killZone->load(['killZoneEnemies', 'spells', 'floor']);

        $output = [
            'pull'                  => $this->dungeonRouteFormatter->formatPull($killZone),
            'route_enemy_forces'    => $dungeonRoute->getEnemyForces(),
            'enemy_forces_required' => $dungeonRoute->mappingVersion->enemy_forces_required,
        ];

        if (Feature::for($user)->active(WalkGraphAggroReachability::class)) {
            $output['aggro_aware']     = true;
            $output['reachable_packs'] = $this->getReachablePacks($dungeonRoute, $enemyIds);
        }

        return Response::structured($output);
    }

    /**
     * The packs on the pull's floor that can be walked to from it, with every enemy the route pulled so far killed.
     * The pull stands on the floor most of its enemies are on, at their centre.
     *
     * @param  list<int>                                                                      $pullEnemyIds
     * @return list<array{id: int, distance: float, unverified?: true, detour_ratio?: float}>
     */
    private function getReachablePacks(DungeonRoute $dungeonRoute, array $pullEnemyIds): array
    {
        $pullableEnemies = $this->getPullableEnemies($dungeonRoute);
        $pullableEnemies->load('floor');

        $pulledEnemyIds = $this->killZoneEnemyRepository
            ->getPullIndicesOfEnemies($dungeonRoute, $pullableEnemies->keys()->all())
            ->mapWithKeys(static fn($row) => [(int)$row->enemy_id => true])
            ->all();
        // A run only meets the faction-only enemies of its own faction
        $dungeonRoute->loadMissing('faction');
        $routeFactionKey = $dungeonRoute->faction->key;
        $standingEnemies = $pullableEnemies->reject(static fn(Enemy $enemy) => isset($pulledEnemyIds[$enemy->id]) ||
            !in_array($enemy->faction, [null, '', Faction::FACTION_ANY, $routeFactionKey], true));

        $pullEnemies  = $pullableEnemies->only($pullEnemyIds);
        $floorId      = $pullEnemies->countBy('floor_id')->sortDesc()->keys()->first();
        $floorEnemies = $pullEnemies->where('floor_id', $floorId);
        if ($floorEnemies->isEmpty()) {
            return [];
        }

        $sumX = 0.0;
        $sumY = 0.0;
        foreach ($floorEnemies as $enemy) {
            $ingameXY = $this->coordinatesService->calculateIngameLocationForMapLocation(
                new LatLng($enemy->lat, $enemy->lng, $enemy->floor),
            );
            $sumX += $ingameXY->getX();
            $sumY += $ingameXY->getY();
        }

        $enemyPacks = $dungeonRoute->mappingVersion->enemyPacks()->where('floor_id', $floorId)->with('floor')->get();
        foreach ($enemyPacks as $enemyPack) {
            /** @var EnemyPack $enemyPack */
            $enemyPack->setRelation('enemies', $standingEnemies->where('enemy_pack_id', $enemyPack->id)->values());
        }

        $reachablePacks = $this->packReachabilityService->getReachablePacksFrom(
            new IngameXY($sumX / $floorEnemies->count(), $sumY / $floorEnemies->count(), $floorEnemies->first()->floor),
            $enemyPacks,
            $standingEnemies,
            (int)config('keystoneguru.api.mcp.pack_neighbour_count'),
        );

        return array_map(static fn(array $reachablePack) => array_filter([
            'id'           => $reachablePack['enemy_pack_id'],
            'distance'     => $reachablePack['distance'],
            'unverified'   => $reachablePack['unverified'] ?: null,
            'detour_ratio' => $reachablePack['detour_ratio'],
        ], static fn($value) => $value !== null), $reachablePacks);
    }
}
