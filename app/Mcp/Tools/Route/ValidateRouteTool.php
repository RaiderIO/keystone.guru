<?php

namespace App\Mcp\Tools\Route;

use App\Mcp\Support\MappingVersionEnemies;
use App\Mcp\Tools\McpTool;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Enemy;
use App\Models\KillZone\KillZone;
use App\Models\User;
use App\Repositories\Interfaces\Npc\NpcEnemyForcesRepositoryInterface;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\User\ApiTokenScope;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Checks one of your routes: total enemy forces against what the dungeon requires, bosses no pull kills, pulls without enemies, and enemies that are in more than one pull. valid is true when none of those problems remain.')]
#[IsReadOnly]
#[IsIdempotent]
class ValidateRouteTool extends McpTool
{
    protected string $name = 'validate_route';

    public function __construct(
        private readonly CoordinatesServiceInterface       $coordinatesService,
        private readonly NpcEnemyForcesRepositoryInterface $npcEnemyForcesRepository,
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
        $dungeonRoute->load(['mappingVersion', 'killZones.killZoneEnemies']);

        $mappingVersion        = $dungeonRoute->mappingVersion;
        $mappingVersionEnemies = new MappingVersionEnemies($this->coordinatesService, $this->npcEnemyForcesRepository, $mappingVersion);

        $enemyForces         = $dungeonRoute->getEnemyForces();
        $enemyForcesRequired = $dungeonRoute->teeming ? $mappingVersion->enemy_forces_required_teeming : $mappingVersion->enemy_forces_required;

        /** @var array<int, list<int>> $pullIndicesByEnemyId */
        $pullIndicesByEnemyId = [];
        $emptyPulls           = [];
        foreach ($dungeonRoute->killZones as $killZone) {
            /** @var KillZone $killZone */
            if ($killZone->killZoneEnemies->isEmpty()) {
                $emptyPulls[] = ['pull_id' => $killZone->id, 'index' => $killZone->index];
            }

            foreach ($killZone->killZoneEnemies as $killZoneEnemy) {
                $pullIndicesByEnemyId[(int)$killZoneEnemy->enemy_id][] = $killZone->index;
            }
        }

        $overlappingEnemies = [];
        foreach ($pullIndicesByEnemyId as $enemyId => $pullIndices) {
            if (count($pullIndices) > 1) {
                $overlappingEnemies[] = ['enemy_id' => $enemyId, 'pull_indices' => $pullIndices];
            }
        }

        $missingBosses = $mappingVersionEnemies->getEnemies()
            ->filter(fn(Enemy $enemy) => $mappingVersionEnemies->isBoss($enemy) &&
                $this->appearsInRoute($enemy, $dungeonRoute) &&
                !isset($pullIndicesByEnemyId[$enemy->id]))
            ->map(static fn(Enemy $enemy) => [
                'enemy_id' => $enemy->id,
                'npc_id'   => $enemy->npc_id,
                'name'     => __($enemy->npc->name ?? ''),
            ])
            ->values()
            ->all();

        return Response::structured([
            'valid' => $enemyForces >= $enemyForcesRequired &&
                $missingBosses === [] &&
                $emptyPulls === [] &&
                $overlappingEnemies === [],
            'enemy_forces'          => $enemyForces,
            'enemy_forces_required' => $enemyForcesRequired,
            'enemy_forces_missing'  => max(0, $enemyForcesRequired - $enemyForces),
            'missing_bosses'        => $missingBosses,
            'empty_pulls'           => $emptyPulls,
            'overlapping_enemies'   => $overlappingEnemies,
        ]);
    }

    /**
     * Whether the enemy exists in the route's version of the dungeon: the right difficulty, seasonal rotation and
     * teeming state.
     */
    private function appearsInRoute(Enemy $enemy, DungeonRoute $dungeonRoute): bool
    {
        if ($enemy->dungeon_difficulty !== null && $enemy->dungeon_difficulty !== $dungeonRoute->dungeon_difficulty) {
            return false;
        }

        if ($enemy->seasonal_index !== null && $enemy->seasonal_index !== $dungeonRoute->seasonal_index) {
            return false;
        }

        if ($enemy->teeming === Enemy::TEEMING_VISIBLE && !$dungeonRoute->teeming) {
            return false;
        }

        return !($enemy->teeming === Enemy::TEEMING_HIDDEN && $dungeonRoute->teeming);
    }
}
