<?php

namespace App\Mcp\Tools\GameData;

use App\Mcp\Exceptions\McpToolException;
use App\Mcp\Support\NpcFormatter;
use App\Mcp\Support\SpellFormatter;
use App\Mcp\Tools\McpTool;
use App\Models\Affix;
use App\Models\GameVersion\GameVersion;
use App\Models\Spell\Spell;
use App\Models\User;
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
    Returns one NPC in full: everything list_dungeon_npcs gives, plus the spells it has been seen casting (name,
    description, school, dispel type, mechanic, cast time and duration in milliseconds; hidden_on_map marks spells the
    site does not show on the map). Give the dungeon for its enemy forces there, and a key level (with affix keys)
    for its scaled health.
    TEXT)]
#[IsReadOnly]
#[IsIdempotent]
class GetNpcTool extends McpTool
{
    protected string $name = 'get_npc';

    public function __construct(
        private readonly NpcFormatter                      $npcFormatter,
        private readonly SpellFormatter                    $spellFormatter,
        private readonly NpcRepositoryInterface            $npcRepository,
        private readonly NpcEnemyForcesRepositoryInterface $npcEnemyForcesRepository,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'npc_id'    => $schema->integer()->description('The NPC id, from get_dungeon_packs or list_dungeon_npcs.')->required(),
            'dungeon'   => $schema->string()->description('The dungeon key or slug, for the NPC\'s enemy forces there.'),
            'key_level' => $schema->integer()->min(1)->max(40)->description('The keystone level to scale health to.'),
            'affixes'   => $schema->array()->items($schema->string())->description('Affix keys the key runs with, e.g. Fortified or Tyrannical.'),
        ];
    }

    protected function requiredScope(): ApiTokenScope
    {
        return ApiTokenScope::RoutesRead;
    }

    protected function run(Request $request, User $user): Response|ResponseFactory
    {
        $validated = $request->validate([
            'npc_id'    => ['required', 'integer'],
            'dungeon'   => ['nullable', 'string', 'max:255'],
            'key_level' => ['nullable', 'integer', 'min:1', 'max:40'],
            'affixes'   => ['nullable', 'array', 'max:10'],
            'affixes.*' => ['string', Rule::exists(Affix::class, 'key')],
        ]);

        $npc = $this->npcRepository->findWithRelations($validated['npc_id'], NpcFormatter::RELATIONS);
        if ($npc === null) {
            throw new McpToolException(__('mcp.error.npc_not_found', ['npc' => $validated['npc_id']]));
        }

        $gameVersion    = GameVersion::getUserOrDefaultGameVersion();
        $npcEnemyForces = null;
        if (isset($validated['dungeon'])) {
            $mappingVersion = $this->findActiveDungeon($validated['dungeon'])->getCurrentMappingVersion();
            if ($mappingVersion !== null) {
                $gameVersion    = $mappingVersion->gameVersion;
                $npcEnemyForces = $this->npcEnemyForcesRepository->findByMappingVersionAndNpcId($mappingVersion, $npc->id);
            }
        }

        $spells = $npc->spells(false)->with(SpellFormatter::RELATIONS)->get();

        return Response::structured(array_merge(
            $this->npcFormatter->format($npc, $gameVersion, $npcEnemyForces, $validated['key_level'] ?? null, $validated['affixes'] ?? []),
            [
                'spells' => $spells->map(fn(Spell $spell) => array_merge(
                    $this->spellFormatter->format($spell),
                    $spell->hidden_on_map ? ['hidden_on_map' => true] : [],
                ))->values()->all(),
            ],
        ));
    }
}
