<?php

namespace App\Mcp\Tools\GameData;

use App\Mcp\Exceptions\McpToolException;
use App\Mcp\Support\NpcFormatter;
use App\Mcp\Tools\McpTool;
use App\Models\Affix;
use App\Models\Npc\Npc;
use App\Models\User;
use App\Repositories\Interfaces\DungeonRepositoryInterface;
use App\Repositories\Interfaces\DungeonRoute\DungeonRouteRepositoryInterface;
use App\Repositories\Interfaces\Npc\NpcEnemyForcesRepositoryInterface;
use App\Repositories\Interfaces\Npc\NpcRepositoryInterface;
use App\Service\User\ApiTokenScope;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description(<<<'TEXT'
    Lists every unique NPC in a dungeon's current mapping: name, classification, type, enemy forces, base health and,
    given a key level (and affix keys from list_affixes), the health at that key level, plus combat flags and how
    many of it the dungeon holds. Paginated.
    TEXT)]
#[IsReadOnly]
#[IsIdempotent]
class ListDungeonNpcsTool extends McpTool
{
    protected string $name = 'list_dungeon_npcs';

    public function __construct(
        private readonly NpcFormatter                      $npcFormatter,
        private readonly NpcRepositoryInterface            $npcRepository,
        private readonly NpcEnemyForcesRepositoryInterface $npcEnemyForcesRepository,
        DungeonRouteRepositoryInterface                    $dungeonRouteRepository,
        DungeonRepositoryInterface                         $dungeonRepository,
    ) {
        parent::__construct($dungeonRouteRepository, $dungeonRepository);
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'dungeon'   => $schema->string()->description('The dungeon key or slug, from list_dungeons.')->required(),
            'key_level' => $schema->integer()->min(1)->max(40)->description('The keystone level to scale health to.'),
            'affixes'   => $schema->array()->items($schema->string())->description('Affix keys the key runs with, e.g. Fortified or Tyrannical.'),
            'page'      => $schema->integer()->min(1)->description('The page to return, starting at 1.'),
        ];
    }

    protected function requiredScope(): ApiTokenScope
    {
        return ApiTokenScope::RoutesRead;
    }

    protected function run(Request $request, User $user): Response|ResponseFactory
    {
        $validated = $request->validate([
            'dungeon'   => ['required', 'string', 'max:255'],
            'key_level' => ['nullable', 'integer', 'min:1', 'max:40'],
            'affixes'   => ['nullable', 'array', 'max:10'],
            'affixes.*' => ['string', Rule::exists(Affix::class, 'key')],
            'page'      => ['nullable', 'integer', 'min:1'],
        ]);

        $dungeon        = $this->findActiveDungeon($validated['dungeon']);
        $mappingVersion = $dungeon->getCurrentMappingVersion();
        if ($mappingVersion === null) {
            throw new McpToolException(__('mcp.error.dungeon_not_found', ['dungeon' => $validated['dungeon']]));
        }

        $enemyCountsByNpcId = $mappingVersion->enemies()
            ->reorder()
            ->toBase()
            ->whereNotNull('npc_id')
            ->selectRaw('npc_id, count(*) as enemy_count')
            ->groupBy('npc_id')
            ->pluck('enemy_count', 'npc_id');

        $page    = (int)($validated['page'] ?? 1);
        $perPage = (int)config('keystoneguru.api.mcp.npcs_per_page');
        $npcIds  = $enemyCountsByNpcId->keys()->map(static fn($id) => (int)$id)->sort()->values();

        $npcs = $this->npcRepository->getByIdsWithRelations($npcIds->forPage($page, $perPage)->all(), NpcFormatter::RELATIONS);

        $npcEnemyForces = $this->npcEnemyForcesRepository->getByMappingVersionKeyedByNpcId($mappingVersion, $npcs->pluck('id'));

        $keyLevel = $validated['key_level'] ?? null;
        $affixes  = $validated['affixes'] ?? [];

        return Response::structured([
            'dungeon'  => $dungeon->key,
            'page'     => $page,
            'per_page' => $perPage,
            'total'    => $npcIds->count(),
            'npcs'     => $npcs->map(fn(Npc $npc) => array_merge(
                $this->npcFormatter->format($npc, $mappingVersion->gameVersion, $npcEnemyForces->get($npc->id), $keyLevel, $affixes),
                ['enemy_count' => (int)$enemyCountsByNpcId->get($npc->id)],
            ))->values()->all(),
        ]);
    }
}
