<?php

namespace App\Mcp\Tools\GameData;

use App\Mcp\Tools\McpTool;
use App\Models\Affix;
use App\Models\User;
use App\Service\User\ApiTokenScope;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Lists every Mythic+ affix with its key (as list_dungeon_npcs and get_npc take it), name and description.')]
#[IsReadOnly]
#[IsIdempotent]
class ListAffixesTool extends McpTool
{
    protected string $name = 'list_affixes';

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }

    protected function requiredScope(): ApiTokenScope
    {
        return ApiTokenScope::RoutesRead;
    }

    protected function run(Request $request, User $user): Response|ResponseFactory
    {
        return Response::structured([
            'affixes' => Affix::query()->orderBy('id')->get()->map(static fn(Affix $affix) => [
                'id'          => $affix->id,
                'key'         => $affix->key,
                'name'        => __($affix->name),
                'description' => __($affix->description),
            ])->values()->all(),
        ]);
    }
}
