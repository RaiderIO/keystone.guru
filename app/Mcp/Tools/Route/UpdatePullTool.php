<?php

namespace App\Mcp\Tools\Route;

use App\Http\Requests\KillZone\APIKillZoneFormRequest;
use App\Logic\Structs\IngameXY;
use App\Mcp\Exceptions\McpToolException;
use App\Mcp\Support\DungeonRouteFormatter;
use App\Mcp\Tools\McpTool;
use App\Mcp\Tools\Route\Concerns\ResolvesPullEnemies;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\KillZone\KillZone;
use App\Models\User;
use App\Repositories\Interfaces\DungeonRoute\DungeonRouteRepositoryInterface;
use App\Repositories\Interfaces\Floor\FloorRepositoryInterface;
use App\Repositories\Interfaces\KillZone\KillZoneEnemyRepositoryInterface;
use App\Repositories\Interfaces\SpellRepositoryInterface;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\KillZone\KillZoneServiceInterface;
use App\Service\User\ApiTokenScope;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;

#[Description(<<<'TEXT'
    Edits one pull of one of your routes, like the route editor's pull workbench. Every field is optional and only
    the fields you pass change:
    - add_pack_ids / add_enemy_ids / remove_enemy_ids: change which enemies the pull kills. An enemy can only be in
      one pull; enemies already pulled elsewhere are refused.
    - spell_ids: the spells cast during the pull (Bloodlust, defensive or offensive cooldowns), from
      get_route_options. Replaces the pull's spells; pass [] to clear them.
    - kill_area: where the pull is killed, as {floor_id, x, y} in in-game coordinates on a floor of the route's
      dungeon. clear_kill_area removes it.
    - color (e.g. #ff0000) and description (a short note shown in the pull list).
    TEXT)]
class UpdatePullTool extends McpTool
{
    use ResolvesPullEnemies;

    protected string $name = 'update_pull';

    public function __construct(
        private readonly KillZoneServiceInterface         $killZoneService,
        private readonly CoordinatesServiceInterface      $coordinatesService,
        private readonly DungeonRouteFormatter            $dungeonRouteFormatter,
        private readonly DungeonRouteRepositoryInterface  $dungeonRouteRepository,
        private readonly KillZoneEnemyRepositoryInterface $killZoneEnemyRepository,
        private readonly SpellRepositoryInterface         $spellRepository,
        private readonly FloorRepositoryInterface         $floorRepository,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'route'            => $schema->string()->description('The route\'s public_key.')->required(),
            'pull_id'          => $schema->integer()->description('The id of the pull to edit, from get_route.')->required(),
            'add_pack_ids'     => $schema->array()->items($schema->integer())->description('Packs whose enemies are added to the pull.'),
            'add_enemy_ids'    => $schema->array()->items($schema->integer())->description('Individual enemies added to the pull.'),
            'remove_enemy_ids' => $schema->array()->items($schema->integer())->description('Enemies removed from the pull.'),
            'spell_ids'        => $schema->array()->items($schema->integer())->description('The pull\'s spells, replacing the current ones.'),
            'kill_area'        => $schema->object(static fn(JsonSchema $schema) => [
                'floor_id' => $schema->integer()->required(),
                'x'        => $schema->number()->required(),
                'y'        => $schema->number()->required(),
            ])->description('Where the pull is killed, in in-game coordinates.'),
            'clear_kill_area' => $schema->boolean()->description('Removes the pull\'s kill area.'),
            'color'           => $schema->string()->pattern('^#([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$')->description('The pull\'s color.'),
            'description'     => $schema->string()->max(KillZone::DESCRIPTION_MAX_LENGTH)->description('A note on the pull; an empty string clears it.'),
        ];
    }

    protected function requiredScope(): ApiTokenScope
    {
        return ApiTokenScope::RoutesWrite;
    }

    protected function run(Request $request, User $user): Response|ResponseFactory
    {
        $validated = $request->validate([
            'route'              => ['required', 'string', 'max:255'],
            'pull_id'            => ['required', 'integer'],
            'add_pack_ids'       => ['nullable', 'array', 'max:100'],
            'add_pack_ids.*'     => ['integer'],
            'add_enemy_ids'      => ['nullable', 'array', 'max:200'],
            'add_enemy_ids.*'    => ['integer'],
            'remove_enemy_ids'   => ['nullable', 'array', 'max:200'],
            'remove_enemy_ids.*' => ['integer'],
            'spell_ids'          => ['nullable', 'array', 'max:50'],
            'spell_ids.*'        => ['integer'],
            'kill_area'          => ['nullable', 'array'],
            'kill_area.floor_id' => ['required_with:kill_area', 'integer'],
            'kill_area.x'        => ['required_with:kill_area', 'numeric'],
            'kill_area.y'        => ['required_with:kill_area', 'numeric'],
            'clear_kill_area'    => ['nullable', 'boolean', 'prohibits:kill_area'],
            'color'              => ['nullable', 'string'],
            'description'        => ['nullable', 'string'],
        ]);

        $dungeonRoute = $this->findOwnDungeonRoute($user, $validated['route'], 'edit');

        if (!$dungeonRoute->killZones()->whereKey($validated['pull_id'])->exists()) {
            throw new McpToolException(__('mcp.error.pull_not_found', ['pull' => $validated['pull_id']]));
        }

        $this->consumeRateLimit($user, 'edit_pull', (int)config('keystoneguru.api.mcp.edit_pull_per_hour'));

        $savedKillZone = DB::transaction(function () use ($dungeonRoute, $user, $validated): KillZone {
            $this->lockDungeonRoute($dungeonRoute);

            /** @var KillZone $killZone */
            $killZone = $dungeonRoute->killZones()->whereKey($validated['pull_id'])->with(['killZoneEnemies', 'killZoneSpells'])->firstOrFail();

            $data = [
                'color'       => $validated['color'] ?? $killZone->color,
                'description' => array_key_exists('description', $validated) ? $validated['description'] : $killZone->description,
                'index'       => $killZone->index,
                'enemies'     => $this->resolveEnemyIds($dungeonRoute, $killZone, $validated),
                'spells'      => array_key_exists('spell_ids', $validated) && $validated['spell_ids'] !== null ?
                    $this->resolveSpellIds($validated['spell_ids']) :
                    $killZone->killZoneSpells->pluck('spell_id')->map(static fn($id) => (int)$id)->all(),
            ] + $this->resolveKillArea($dungeonRoute, $killZone, $validated);

            $killZoneData       = $this->validateWithFormRequest(APIKillZoneFormRequest::class, $user, array_filter($data, static fn($value) => $value !== null));
            $killZoneData['id'] = $killZone->id;
            // Explicit nulls clear the kill area; the Form Request drops keys it was not given
            foreach (['floor_id', 'lat', 'lng'] as $key) {
                $killZoneData[$key] ??= $data[$key];
            }
            $killZoneData['description'] ??= $data['description'];
            $killZoneData['enemies'] ??= [];
            $killZoneData['spells'] ??= [];

            return $this->killZoneService->saveKillZone($dungeonRoute, $killZoneData);
        }, 3);
        $savedKillZone->load(['killZoneEnemies', 'spells', 'floor']);

        return Response::structured([
            'pull'                  => $this->dungeonRouteFormatter->formatPull($savedKillZone),
            'route_enemy_forces'    => $dungeonRoute->getEnemyForces(),
            'enemy_forces_required' => $dungeonRoute->mappingVersion->enemy_forces_required,
        ]);
    }

    /**
     * @param  array<string, mixed> $validated
     * @return list<int>
     *
     * @throws McpToolException
     */
    private function resolveEnemyIds(DungeonRoute $dungeonRoute, KillZone $killZone, array $validated): array
    {
        $currentEnemyIds = $killZone->killZoneEnemies->pluck('enemy_id')->map(static fn($id) => (int)$id)->all();

        $addPackIds  = array_map('intval', $validated['add_pack_ids'] ?? []);
        $addEnemyIds = array_map('intval', $validated['add_enemy_ids'] ?? []);

        $addedEnemyIds = [];
        if ($addPackIds !== [] || $addEnemyIds !== []) {
            $addedEnemyIds = array_values(array_diff($this->getEnemyIdsOfPacksAndEnemies($dungeonRoute, $addPackIds, $addEnemyIds), $currentEnemyIds));
            $this->ensureNotPulledYet($dungeonRoute, $addedEnemyIds, $killZone->id);
        }

        $result = array_values(array_diff(
            array_unique(array_merge($currentEnemyIds, $addedEnemyIds)),
            array_map('intval', $validated['remove_enemy_ids'] ?? []),
        ));
        sort($result);

        return $result;
    }

    /**
     * Only the spells the editor offers for a pull.
     *
     * @param  array<int, int> $spellIds
     * @return list<int>
     *
     * @throws McpToolException
     */
    private function resolveSpellIds(array $spellIds): array
    {
        $spellIds        = array_values(array_unique(array_map('intval', $spellIds)));
        $selectableIds   = $this->spellRepository->getSelectableIds($spellIds)->all();
        $unknownSpellIds = array_diff($spellIds, $selectableIds);
        if ($unknownSpellIds !== []) {
            throw new McpToolException(__('mcp.error.unknown_spell_ids', ['ids' => implode(', ', $unknownSpellIds)]));
        }

        return $spellIds;
    }

    /**
     * The kill area as the editor stores it: a floor and a map location, converted from the in-game coordinates.
     *
     * @param  array<string, mixed>                                        $validated
     * @return array{floor_id: int|null, lat: float|null, lng: float|null}
     *
     * @throws McpToolException
     */
    private function resolveKillArea(DungeonRoute $dungeonRoute, KillZone $killZone, array $validated): array
    {
        if ($validated['clear_kill_area'] ?? false) {
            return ['floor_id' => null, 'lat' => null, 'lng' => null];
        }

        if (!isset($validated['kill_area'])) {
            return ['floor_id' => $killZone->floor_id, 'lat' => $killZone->lat, 'lng' => $killZone->lng];
        }

        $floor = $this->floorRepository->findNonFacadeInDungeon((int)$validated['kill_area']['floor_id'], $dungeonRoute->dungeon_id);
        if ($floor === null) {
            throw new McpToolException(__('mcp.error.unknown_floor', ['floor' => $validated['kill_area']['floor_id']]));
        }

        $latLng = $this->coordinatesService->calculateMapLocationForIngameLocation(
            new IngameXY((float)$validated['kill_area']['x'], (float)$validated['kill_area']['y'], $floor),
        );

        return ['floor_id' => $floor->id, 'lat' => $latLng->getLat(), 'lng' => $latLng->getLng()];
    }
}
