<?php

namespace App\Mcp\Tools\Route;

use App\Mcp\Support\DungeonRouteFormatter;
use App\Mcp\Tools\McpTool;
use App\Models\User;
use App\Service\User\ApiTokenScope;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Returns one of your routes with its pulls in kill order: each pull\'s id, enemies, spells, color, description and kill area (in-game coordinates), plus the route\'s total enemy forces.')]
#[IsReadOnly]
#[IsIdempotent]
class GetRouteTool extends McpTool
{
    protected string $name = 'get_route';

    public function __construct(
        private readonly DungeonRouteFormatter $dungeonRouteFormatter,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'route' => $schema->string()->description('The route\'s public_key.')->required(),
        ];
    }

    protected function requiredScope(): ApiTokenScope
    {
        return ApiTokenScope::RoutesRead;
    }

    protected function run(Request $request, User $user): Response|ResponseFactory
    {
        $validated = $request->validate([
            'route' => ['required', 'string', 'max:255'],
        ]);

        $dungeonRoute = $this->findOwnDungeonRoute($user, $validated['route'], 'view');

        return Response::structured($this->dungeonRouteFormatter->formatRoute($dungeonRoute));
    }
}
