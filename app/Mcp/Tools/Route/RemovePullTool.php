<?php

namespace App\Mcp\Tools\Route;

use App\Mcp\Exceptions\McpToolException;
use App\Mcp\Tools\McpTool;
use App\Mcp\Tools\Route\Concerns\SavesPullOrder;
use App\Models\KillZone\KillZone;
use App\Models\User;
use App\Repositories\Interfaces\DungeonRepositoryInterface;
use App\Repositories\Interfaces\DungeonRoute\DungeonRouteRepositoryInterface;
use App\Service\KillZone\Exceptions\KillZoneDeleteRefusedException;
use App\Service\KillZone\KillZoneServiceInterface;
use App\Service\User\ApiTokenScope;
use Exception;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[Description('Removes a single pull from one of your routes; its enemies become free to pull again. The other pulls keep their order and are renumbered 1..n. Removes nothing else - there is no way to delete a route or several pulls at once.')]
#[IsDestructive]
class RemovePullTool extends McpTool
{
    use SavesPullOrder;

    protected string $name = 'remove_pull';

    public function __construct(
        private readonly KillZoneServiceInterface $killZoneService,
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
            'route'   => $schema->string()->description('The route\'s public_key.')->required(),
            'pull_id' => $schema->integer()->description('The id of the pull to remove, from get_route.')->required(),
        ];
    }

    protected function requiredScope(): ApiTokenScope
    {
        return ApiTokenScope::RoutesWrite;
    }

    protected function run(Request $request, User $user): Response|ResponseFactory
    {
        $validated = $request->validate([
            'route'   => ['required', 'string', 'max:255'],
            'pull_id' => ['required', 'integer'],
        ]);

        $dungeonRoute = $this->findOwnDungeonRoute($user, $validated['route'], 'edit');

        /** @var KillZone|null $killZone */
        $killZone = $dungeonRoute->killZones()->whereKey($validated['pull_id'])->first();
        if ($killZone === null) {
            throw new McpToolException(__('mcp.error.pull_not_found', ['pull' => $validated['pull_id']]));
        }

        $this->consumeRateLimit($user, 'edit_pull', (int)config('keystoneguru.api.mcp.edit_pull_per_hour'));

        try {
            $enemyForces = $this->killZoneService->deleteKillZone($dungeonRoute, $killZone);
        } catch (KillZoneDeleteRefusedException $exception) {
            report($exception);

            throw new McpToolException(__('mcp.error.unable_to_delete_pull'));
        } catch (Exception $exception) {
            report($exception);

            throw new McpToolException(__('mcp.error.pull_not_found', ['pull' => $validated['pull_id']]));
        }

        // Close the gap the removed pull left, so pull numbers stay 1..n like the editor shows them
        $remainingKillZones = $dungeonRoute->killZones()->get()->keyBy('id')->all();
        $this->savePullOrder($dungeonRoute, $user, $remainingKillZones, array_keys($remainingKillZones));

        return Response::structured([
            'removed_pull_id'    => $killZone->id,
            'route_enemy_forces' => $enemyForces,
            'pull_count'         => count($remainingKillZones),
        ]);
    }
}
