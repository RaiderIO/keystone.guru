<?php

namespace Tests\Feature\Mcp;

use App\Features\Mcp;
use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteAffixGroup;
use App\Models\DungeonRoute\DungeonRouteAttribute;
use App\Models\DungeonRoute\DungeonRoutePlayerClass;
use App\Models\DungeonRoute\DungeonRoutePlayerRace;
use App\Models\DungeonRoute\DungeonRoutePlayerSpecialization;
use App\Models\Enemy;
use App\Models\EnemyPack;
use App\Models\KillZone\KillZone;
use App\Models\KillZone\KillZoneEnemy;
use App\Models\Laratrust\Role;
use App\Models\Mapping\MappingVersion;
use App\Models\PublishedState;
use App\Models\User;
use App\Service\User\ApiTokenScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Laravel\Mcp\Server\Testing\TestResponse;
use Laravel\Passport\Passport;
use Laravel\Pennant\Feature;
use Override;
use Tests\Feature\Traits\ProvidesDungeon;
use Tests\Feature\Traits\UsesPassportKeys;
use Tests\TestCases\PublicTestCase;

/**
 * Calls MCP tools as a freshly created user holding the given token scopes, with the Mcp feature active for them,
 * and cleans every user, route and pull it made up again.
 */
abstract class McpTestCase extends PublicTestCase
{
    use ProvidesDungeon;
    use UsesPassportKeys;

    /** @var array<int, User> */
    private array $createdUsers = [];

    /** @var array<int, DungeonRoute> */
    private array $createdDungeonRoutes = [];

    /** @var array<int, int> */
    private array $mcpActiveUserIds = [];

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        // Resolving the api guard builds Passport's resource server, which needs a key pair even for a fake token
        $this->usePassportKeys();

        // Defined rather than activated per user: the array store the tests run on drops activated values whenever a
        // (sync) queued job finishes, which a route save dispatches
        Feature::define(Mcp::class, fn(?User $user) => $user !== null && in_array($user->id, $this->mcpActiveUserIds, true));
    }

    #[Override]
    protected function tearDown(): void
    {
        foreach ($this->createdDungeonRoutes as $dungeonRoute) {
            $this->deleteDungeonRoute($dungeonRoute->id);
        }
        $this->createdDungeonRoutes = [];

        foreach ($this->createdUsers as $user) {
            DungeonRoute::query()->where('author_id', $user->id)->pluck('id')->each($this->deleteDungeonRoute(...));
            Passport::token()->newQuery()->where('user_id', $user->id)->delete();
            $user->syncRoles([]);
            $user->delete();
        }
        $this->createdUsers = [];

        parent::tearDown();
    }

    protected function createUser(string $role = Role::ROLE_USER, bool $mcpActive = true): User
    {
        $user = User::factory()->create();
        $user->addRole(Role::firstWhere('name', $role));
        $this->createdUsers[] = $user;

        if ($mcpActive) {
            $this->mcpActiveUserIds[] = $user->id;
        }

        return $user;
    }

    /**
     * @param array<int, ApiTokenScope> $scopes
     */
    protected function actingAsMcpUser(User $user, array $scopes = [ApiTokenScope::RoutesWrite]): User
    {
        Passport::actingAs($user, array_map(static fn(ApiTokenScope $scope) => $scope->value, $scopes));

        return $user;
    }

    /**
     * @return array{0: Dungeon, 1: MappingVersion}
     */
    protected function findPackDungeon(bool $requireDungeonStart = false): array
    {
        [$dungeon, $mappingVersion] = $this->findDungeon(
            challengeMode: true,
            dungeonActive: true,
            minEnemies: 10,
            minEnemyPacks: 3,
            constraint: static fn($query) => $query->whereHas('expansion', static fn($expansion) => $expansion->where('active', true)),
            resolve: $requireDungeonStart ? static fn($dungeon, MappingVersion $mappingVersion) => $mappingVersion->dungeonStarts()->exists() ? true : null : null,
        );

        return [$dungeon, $mappingVersion];
    }

    /**
     * Packs of the mapping version that hold at least one pullable enemy, with those enemies loaded - never the MDT
     * placeholders the tools leave out.
     *
     * @return EloquentCollection<int, EnemyPack>
     */
    protected function getPacksWithEnemies(MappingVersion $mappingVersion, int $count): EloquentCollection
    {
        $pullable = static fn($query) => $query->where(self::excludeMdtPlaceholders(...));

        $enemyPacks = $mappingVersion->enemyPacks()
            ->whereHas('enemies', $pullable)
            ->with(['enemies' => $pullable])
            ->orderBy('id')
            ->limit($count)
            ->get();
        $this->assertCount($count, $enemyPacks, 'The picked dungeon lacks packs with enemies');

        return $enemyPacks;
    }

    protected function createDungeonRoute(User $author, Dungeon $dungeon, MappingVersion $mappingVersion): DungeonRoute
    {
        $dungeonRoute = DungeonRoute::factory()->create([
            'author_id'          => $author->id,
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $mappingVersion->id,
            'expires_at'         => null,
            'team_id'            => null,
            'published_state_id' => PublishedState::ALL[PublishedState::UNPUBLISHED],
        ]);
        $this->createdDungeonRoutes[] = $dungeonRoute;

        return $dungeonRoute;
    }

    /**
     * @param array<int, int> $enemyIds
     */
    protected function createPull(DungeonRoute $dungeonRoute, int $index, array $enemyIds, string $color = '#000000'): KillZone
    {
        $killZone = KillZone::create([
            'dungeon_route_id' => $dungeonRoute->id,
            'color'            => $color,
            'index'            => $index,
        ]);

        foreach (Enemy::query()->whereIn('id', $enemyIds)->get() as $enemy) {
            KillZoneEnemy::create([
                'kill_zone_id' => $killZone->id,
                'enemy_id'     => $enemy->id,
                'npc_id'       => $enemy->npc_id,
                'mdt_id'       => $enemy->mdt_id,
            ]);
        }

        return $killZone;
    }

    /**
     * @return array<string, mixed>
     */
    protected function structuredContent(TestResponse $response): array
    {
        $structuredContent = (fn() => $this->structuredContent())->call($response);
        $this->assertIsArray($structuredContent, 'The tool returned no structured content');

        return $structuredContent;
    }

    /**
     * Query constraint leaving out the MDT placeholders the MCP tools neither list nor pull.
     *
     * @param Builder<Enemy> $builder
     */
    protected static function excludeMdtPlaceholders(Builder $builder): void
    {
        $builder->whereNull('seasonal_type')->orWhereNot('seasonal_type', Enemy::SEASONAL_TYPE_MDT_PLACEHOLDER);
    }

    private function deleteDungeonRoute(int $dungeonRouteId): void
    {
        // KillZone::deleting takes the pull's enemies and spells with it
        KillZone::query()->where('dungeon_route_id', $dungeonRouteId)->get()->each(static fn(KillZone $killZone) => $killZone->delete());
        foreach ([DungeonRouteAffixGroup::class, DungeonRouteAttribute::class, DungeonRoutePlayerClass::class, DungeonRoutePlayerSpecialization::class, DungeonRoutePlayerRace::class] as $childModel) {
            $childModel::query()->where('dungeon_route_id', $dungeonRouteId)->delete();
        }
        DungeonRoute::find($dungeonRouteId)?->delete();
    }
}
