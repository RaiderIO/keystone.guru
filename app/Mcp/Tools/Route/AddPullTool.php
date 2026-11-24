<?php

namespace App\Mcp\Tools\Route;

use App\Http\Requests\KillZone\APIKillZoneFormRequest;
use App\Mcp\Exceptions\McpToolException;
use App\Mcp\Support\DungeonRouteFormatter;
use App\Mcp\Tools\McpTool;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\KillZone\KillZone;
use App\Models\KillZone\KillZoneEnemy;
use App\Models\User;
use App\Service\KillZone\KillZoneServiceInterface;
use App\Service\User\ApiTokenScope;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Adds a pull to the end of one of your routes, killing every enemy of the given packs plus the given individual enemies (ids from get_dungeon_packs). An enemy can only be in one pull: enemies already pulled elsewhere are refused. Returns the new pull and the route\'s enemy forces.')]
class AddPullTool extends McpTool
{
    protected string $name = 'add_pull';

    public function __construct(
        private readonly KillZoneServiceInterface $killZoneService,
        private readonly DungeonRouteFormatter    $dungeonRouteFormatter,
    ) {
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
            'color'       => $schema->string()->pattern('^#([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$')->description('The pull\'s color, e.g. #ff0000. Random when left out.'),
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

        $enemyIds = $this->resolveEnemyIds($dungeonRoute, $validated['pack_ids'] ?? [], $validated['enemy_ids'] ?? []);

        $this->consumeRateLimit($user, 'edit_pull', (int)config('keystoneguru.api.mcp.edit_pull_per_hour'));

        $killZone = DB::transaction(function () use ($dungeonRoute, $user, $validated, $enemyIds): KillZone {
            // Serializes pull additions per route: two concurrent calls would otherwise both pass the overlap check
            // and take the same next index
            DungeonRoute::query()->whereKey($dungeonRoute->id)->lockForUpdate()->first();

            $this->ensureNotPulledYet($dungeonRoute, $enemyIds);

            $killZoneData = $this->validateWithFormRequest(APIKillZoneFormRequest::class, $user, array_filter([
                'color'       => $validated['color'] ?? randomHexColor(),
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

        return Response::structured([
            'pull'                  => $this->dungeonRouteFormatter->formatPull($killZone),
            'route_enemy_forces'    => $dungeonRoute->getEnemyForces(),
            'enemy_forces_required' => $dungeonRoute->mappingVersion->enemy_forces_required,
        ]);
    }

    /**
     * @param  array<int, int> $packIds
     * @param  array<int, int> $enemyIds
     * @return list<int>
     *
     * @throws McpToolException
     */
    private function resolveEnemyIds(DungeonRoute $dungeonRoute, array $packIds, array $enemyIds): array
    {
        if ($packIds === [] && $enemyIds === []) {
            throw new McpToolException(__('mcp.error.no_enemies_given'));
        }

        $mappingVersion = $dungeonRoute->mappingVersion;

        $knownPackIds   = $mappingVersion->enemyPacks()->whereIn('id', $packIds)->pluck('id')->map(static fn($id) => (int)$id)->all();
        $unknownPackIds = array_diff($packIds, $knownPackIds);
        if ($unknownPackIds !== []) {
            throw new McpToolException(__('mcp.error.unknown_pack_ids', ['ids' => implode(', ', $unknownPackIds)]));
        }

        $knownEnemyIds   = $mappingVersion->enemies()->whereIn('id', $enemyIds)->pluck('id')->map(static fn($id) => (int)$id)->all();
        $unknownEnemyIds = array_diff($enemyIds, $knownEnemyIds);
        if ($unknownEnemyIds !== []) {
            throw new McpToolException(__('mcp.error.unknown_enemy_ids', ['ids' => implode(', ', $unknownEnemyIds)]));
        }

        $packEnemyIds = $knownPackIds === [] ? [] : $mappingVersion->enemies()
            ->whereIn('enemy_pack_id', $knownPackIds)
            ->pluck('id')
            ->map(static fn($id) => (int)$id)
            ->all();

        $result = array_values(array_unique(array_merge($packEnemyIds, $knownEnemyIds)));
        sort($result);

        return $result;
    }

    /**
     * @param list<int> $enemyIds
     *
     * @throws McpToolException
     */
    private function ensureNotPulledYet(DungeonRoute $dungeonRoute, array $enemyIds): void
    {
        $alreadyPulled = KillZoneEnemy::query()
            ->join('kill_zones', 'kill_zones.id', '=', 'kill_zone_enemies.kill_zone_id')
            ->where('kill_zones.dungeon_route_id', $dungeonRoute->id)
            ->whereIn('kill_zone_enemies.enemy_id', $enemyIds)
            ->orderBy('kill_zone_enemies.enemy_id')
            ->toBase()
            ->get(['kill_zone_enemies.enemy_id', 'kill_zones.index']);

        if ($alreadyPulled->isNotEmpty()) {
            throw new McpToolException(__('mcp.error.enemies_already_pulled', [
                'enemies' => $alreadyPulled->map(static fn($row) => sprintf('%d => %d', $row->enemy_id, $row->index))->implode(', '),
            ]));
        }
    }
}
