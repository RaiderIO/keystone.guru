<?php

namespace App\Mcp\Tools\Route;

use App\Mcp\Support\DungeonRouteFormatter;
use App\Mcp\Tools\McpTool;
use App\Models\User;
use App\Repositories\Interfaces\DungeonRoute\DungeonRouteRepositoryInterface;
use App\Service\User\ApiTokenScope;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Lists the routes you authored that are on no team, most recently updated first, optionally for one dungeon. Paginated.')]
#[IsReadOnly]
#[IsIdempotent]
class ListMyRoutesTool extends McpTool
{
    protected string $name = 'list_my_routes';

    public function __construct(
        private readonly DungeonRouteFormatter           $dungeonRouteFormatter,
        private readonly DungeonRouteRepositoryInterface $dungeonRouteRepository,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'dungeon' => $schema->string()->description('Only routes of this dungeon key or slug.'),
            'page'    => $schema->integer()->min(1)->description('The page to return, starting at 1.'),
        ];
    }

    protected function requiredScope(): ApiTokenScope
    {
        return ApiTokenScope::RoutesRead;
    }

    protected function run(Request $request, User $user): Response|ResponseFactory
    {
        $validated = $request->validate([
            'dungeon' => ['nullable', 'string', 'max:255'],
            'page'    => ['nullable', 'integer', 'min:1'],
        ]);

        $perPage = (int)config('keystoneguru.api.mcp.routes_per_page');
        $page    = (int)($validated['page'] ?? 1);

        $dungeon = isset($validated['dungeon']) ? $this->findActiveDungeon($validated['dungeon']) : null;

        $dungeonRoutes = $this->dungeonRouteRepository->paginateOwnedOutsideTeam($user, $dungeon, $page, $perPage);

        return Response::structured([
            'page'     => $page,
            'per_page' => $perPage,
            'total'    => $dungeonRoutes->total(),
            'routes'   => $dungeonRoutes->getCollection()->map($this->dungeonRouteFormatter->formatSummary(...))->values()->all(),
        ]);
    }
}
