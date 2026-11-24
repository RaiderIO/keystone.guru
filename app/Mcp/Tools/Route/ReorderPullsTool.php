<?php

namespace App\Mcp\Tools\Route;

use App\Mcp\Exceptions\McpToolException;
use App\Mcp\Support\DungeonRouteFormatter;
use App\Mcp\Tools\McpTool;
use App\Mcp\Tools\Route\Concerns\SavesPullOrder;
use App\Models\KillZone\KillZone;
use App\Models\User;
use App\Repositories\Interfaces\DungeonRepositoryInterface;
use App\Repositories\Interfaces\DungeonRoute\DungeonRouteRepositoryInterface;
use App\Service\KillZone\KillZoneServiceInterface;
use App\Service\User\ApiTokenScope;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;

#[Description('Puts the pulls of one of your routes in a new kill order. Give every pull id of the route (from get_route) exactly once, in the desired new order.')]
#[IsIdempotent]
class ReorderPullsTool extends McpTool
{
    use SavesPullOrder;

    protected string $name = 'reorder_pulls';

    public function __construct(
        private readonly KillZoneServiceInterface $killZoneService,
        private readonly DungeonRouteFormatter    $dungeonRouteFormatter,
        DungeonRouteRepositoryInterface           $dungeonRouteRepository,
        DungeonRepositoryInterface                $dungeonRepository,
    ) {
        parent::__construct($dungeonRouteRepository, $dungeonRepository);
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'route'    => $schema->string()->description('The route\'s public_key.')->required(),
            'pull_ids' => $schema->array()->items($schema->integer())->description('Every pull id of the route, in the new kill order.')->required(),
        ];
    }

    protected function requiredScope(): ApiTokenScope
    {
        return ApiTokenScope::RoutesWrite;
    }

    protected function run(Request $request, User $user): Response|ResponseFactory
    {
        $validated = $request->validate([
            'route'      => ['required', 'string', 'max:255'],
            'pull_ids'   => ['required', 'array'],
            'pull_ids.*' => ['integer', 'distinct'],
        ]);

        $dungeonRoute = $this->findOwnDungeonRoute($user, $validated['route'], 'edit');

        /** @var array<int, KillZone> $killZones */
        $killZones = $dungeonRoute->killZones()->get()->keyBy('id')->all();
        $pullIds   = array_map('intval', $validated['pull_ids']);

        $routePullIds = array_keys($killZones);
        sort($routePullIds);
        $sortedPullIds = $pullIds;
        sort($sortedPullIds);
        if ($routePullIds !== $sortedPullIds) {
            throw new McpToolException(__('mcp.error.reorder_mismatch', ['ids' => implode(', ', $routePullIds)]));
        }

        $this->consumeRateLimit($user, 'edit_pull', (int)config('keystoneguru.api.mcp.edit_pull_per_hour'));

        $this->savePullOrder($dungeonRoute, $user, $killZones, $pullIds);

        return Response::structured($this->dungeonRouteFormatter->formatRoute($dungeonRoute->fresh()));
    }
}
