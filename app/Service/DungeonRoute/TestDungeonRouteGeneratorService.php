<?php

namespace App\Service\DungeonRoute;

use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Enemy;
use App\Models\Faction;
use App\Models\KillZone\KillZone;
use App\Models\KillZone\KillZoneEnemy;
use App\Models\Mapping\MappingVersion;
use App\Models\Npc\NpcEnemyForces;
use App\Models\Tags\Tag;
use App\Models\Tags\TagCategory;
use App\Models\User;
use App\Service\DungeonRoute\Exceptions\TestDungeonRouteGeneratorException;
use App\Service\Season\SeasonServiceInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TestDungeonRouteGeneratorService implements TestDungeonRouteGeneratorServiceInterface
{
    /** APP_ENV is `production` on staging too - APP_TYPE is what tells the deployments apart. */
    private const array ALLOWED_APP_TYPES = [
        'local',
        'staging',
    ];

    private const int TARGET_FORCES_PERCENTAGE_MIN = 100;

    private const int TARGET_FORCES_PERCENTAGE_MAX = 112;

    private const int SKIP_PULL_CANDIDATE_PERCENTAGE = 25;

    /** Packs average about three enemies; real routes pull closer to five at a time. */
    private const int PULL_SIZE_MIN = 4;

    private const int PULL_SIZE_MAX = 7;

    public function __construct(
        private readonly SeasonServiceInterface $seasonService,
    ) {
    }

    public function isAvailable(): bool
    {
        $appType = config('app.type');

        // APP_TYPE falls back to `local` when unset - never let that fallback open this up on a production APP_ENV
        return in_array($appType, self::ALLOWED_APP_TYPES, true)
            && ($appType === 'staging' || config('app.env') !== 'production');
    }

    public function generate(Dungeon $dungeon, User $author, int $count, int $publishedStateId): Collection
    {
        $this->ensureAvailable();

        if ($count < 1 || $count > self::MAX_ROUTES_PER_DUNGEON) {
            throw new TestDungeonRouteGeneratorException(
                sprintf('Route count must be between 1 and %d, got %d', self::MAX_ROUTES_PER_DUNGEON, $count),
            );
        }

        $mappingVersion = $dungeon->getCurrentMappingVersion();
        if ($mappingVersion === null) {
            throw new TestDungeonRouteGeneratorException(sprintf('Dungeon %s has no mapping version', $dungeon->key));
        }

        $pullCandidates = $this->getPullCandidates($mappingVersion);
        if ($pullCandidates->isEmpty()) {
            throw new TestDungeonRouteGeneratorException(
                sprintf('Dungeon %s has no enemies on mapping version %d', $dungeon->key, $mappingVersion->id),
            );
        }

        $enemyForcesByKey = $this->getEnemyForcesByKey($mappingVersion);
        $season           = $dungeon->getActiveSeason($this->seasonService);
        $keyLevelMin      = $season === null ? (int)config('keystoneguru.keystone.levels.default_min') : $season->key_level_min;
        $keyLevelMax      = $season === null ? (int)config('keystoneguru.keystone.levels.default_max') : $season->key_level_max;

        $result = collect();
        for ($i = 1; $i <= $count; $i++) {
            $result->push(DB::transaction(fn() => $this->generateRoute(
                $dungeon,
                $mappingVersion,
                $author,
                $publishedStateId,
                sprintf('Test route %d - %s', $i, __($dungeon->name, [], 'en_US')),
                random_int($keyLevelMin, max($keyLevelMin, $keyLevelMax - 5)),
                $keyLevelMax,
                $pullCandidates,
                $enemyForcesByKey,
            )));
        }

        return $result;
    }

    public function deleteGenerated(int $limit, ?User $author = null): array
    {
        $this->ensureAvailable();

        $dungeonRoutes = $this->generatedQuery($author)->orderBy('id')->limit($limit)->get();
        foreach ($dungeonRoutes as $dungeonRoute) {
            $dungeonRoute->delete();
        }

        return [
            'deleted'   => $dungeonRoutes->count(),
            'remaining' => $this->countGenerated($author),
        ];
    }

    public function countGenerated(?User $author = null): int
    {
        return $this->generatedQuery($author)->count();
    }

    /**
     * @param Collection<int, Collection<int, Enemy>> $pullCandidates
     * @param Collection<string, int>                 $enemyForcesByKey
     */
    private function generateRoute(
        Dungeon        $dungeon,
        MappingVersion $mappingVersion,
        User           $author,
        int            $publishedStateId,
        string         $title,
        int            $levelMin,
        int            $keyLevelMax,
        Collection     $pullCandidates,
        Collection     $enemyForcesByKey,
    ): DungeonRoute {
        $dungeonRoute = DungeonRoute::create([
            'public_key'         => DungeonRoute::generateRandomPublicKey(),
            'author_id'          => $author->id,
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $mappingVersion->id,
            'season_id'          => $dungeon->getActiveSeason($this->seasonService)?->id,
            'faction_id'         => Faction::ALL[Faction::FACTION_UNSPECIFIED],
            'published_state_id' => $publishedStateId,
            'title'              => $title,
            'description'        => '',
            'level_min'          => $levelMin,
            'level_max'          => min($keyLevelMax, $levelMin + random_int(0, 5)),
            'expires_at'         => null,
        ]);

        Tag::create([
            'context_id'      => $author->id,
            'context_class'   => User::class,
            'tag_category_id' => TagCategory::ALL[TagCategory::DUNGEON_ROUTE_PERSONAL],
            'model_id'        => $dungeonRoute->id,
            'model_class'     => DungeonRoute::class,
            'name'            => self::TAG_NAME,
            'color'           => null,
        ]);

        $targetForces = (int)ceil(
            $mappingVersion->enemy_forces_required * random_int(self::TARGET_FORCES_PERCENTAGE_MIN, self::TARGET_FORCES_PERCENTAGE_MAX) / 100,
        );
        $this->createPulls($dungeonRoute, $pullCandidates, $enemyForcesByKey, $targetForces);

        // published_at is guarded, so create() would drop it
        $dungeonRoute->forceFill([
            'enemy_forces' => $dungeonRoute->getEnemyForces(),
            'published_at' => Carbon::now(),
        ])->save();

        return $dungeonRoute;
    }

    /**
     * @return Builder<DungeonRoute>
     */
    private function generatedQuery(?User $author): Builder
    {
        return DungeonRoute::query()
            ->when($author !== null, static fn(Builder $query) => $query->where('author_id', $author->id))
            ->whereExists(static fn($query) => $query
                ->select(DB::raw(1))
                ->from('tags')
                ->whereColumn('tags.model_id', 'dungeon_routes.id')
                ->whereColumn('tags.context_id', 'dungeon_routes.author_id')
                ->where('tags.context_class', User::class)
                ->where('tags.model_class', DungeonRoute::class)
                ->where('tags.tag_category_id', TagCategory::ALL[TagCategory::DUNGEON_ROUTE_PERSONAL])
                ->where('tags.name', self::TAG_NAME));
    }

    /**
     * Merges consecutive candidates on the same floor into pulls of a random minimum size until the route
     * reaches the target enemy forces or the candidates run out, then adds every boss that was not pulled
     * yet as a pull of its own.
     *
     * @param Collection<int, Collection<int, Enemy>> $pullCandidates
     * @param Collection<string, int>                 $enemyForcesByKey
     */
    private function createPulls(
        DungeonRoute $dungeonRoute,
        Collection   $pullCandidates,
        Collection   $enemyForcesByKey,
        int          $targetForces,
    ): void {
        // Skipping a random part of the candidates on the first pass keeps routes of the same dungeon apart
        [$skipped, $firstPass] = $pullCandidates->partition(static fn() => random_int(1, 100) <= self::SKIP_PULL_CANDIDATE_PERCENTAGE);

        $index          = 1;
        $forces         = 0;
        $killedKeys     = [];
        $pulledEnemyIds = [];
        $pull           = collect();
        $pullSize       = random_int(self::PULL_SIZE_MIN, self::PULL_SIZE_MAX);

        $flush = function () use ($dungeonRoute, $enemyForcesByKey, &$index, &$forces, &$killedKeys, &$pulledEnemyIds, &$pull, &$pullSize): void {
            $this->createPull($dungeonRoute, $pull, $index++);

            foreach ($pull as $enemy) {
                $pulledEnemyIds[] = $enemy->id;

                $key = $this->getEnemyKey($enemy);
                if ($key !== null && !isset($killedKeys[$key])) {
                    $killedKeys[$key] = true;
                    $forces += $enemyForcesByKey->get($key, 0);
                }
            }

            $pull     = collect();
            $pullSize = random_int(self::PULL_SIZE_MIN, self::PULL_SIZE_MAX);
        };

        foreach ($firstPass->concat($skipped) as $enemies) {
            if ($pull->isNotEmpty() && $pull->first()->floor_id !== $enemies->first()->floor_id) {
                $flush();
            }

            $pull = $pull->concat($enemies);

            if ($pull->count() >= $pullSize) {
                $flush();

                if ($forces >= $targetForces) {
                    break;
                }
            }
        }

        if ($pull->isNotEmpty()) {
            $flush();
        }

        $remainingBosses = $pullCandidates->flatten(1)
            ->filter(static fn(Enemy $enemy) => $enemy->npc?->isBoss() && !in_array($enemy->id, $pulledEnemyIds, true));
        foreach ($remainingBosses as $boss) {
            $this->createPull($dungeonRoute, collect([$boss]), $index++);
        }
    }

    /**
     * @param Collection<int, Enemy> $enemies
     */
    private function createPull(DungeonRoute $dungeonRoute, Collection $enemies, int $index): void
    {
        $killZone = KillZone::create([
            'dungeon_route_id' => $dungeonRoute->id,
            'floor_id'         => $enemies->first()->floor_id,
            'color'            => sprintf('#%06X', random_int(0, 0xFFFFFF)),
            'description'      => '',
            'index'            => $index,
            'lat'              => $enemies->avg('lat'),
            'lng'              => $enemies->avg('lng'),
        ]);

        KillZoneEnemy::insert($enemies->map(static fn(Enemy $enemy) => [
            'kill_zone_id' => $killZone->id,
            'npc_id'       => $enemy->mdt_npc_id ?? $enemy->npc_id,
            'mdt_id'       => $enemy->mdt_id,
            'enemy_id'     => $enemy->id,
        ])->values()->all());
    }

    /**
     * Every pack in pack order, followed by every enemy outside a pack on its own, so a dungeon whose packs
     * fall short of the required forces can still be completed.
     *
     * @return Collection<int, Collection<int, Enemy>>
     */
    private function getPullCandidates(MappingVersion $mappingVersion): Collection
    {
        $enemies = Enemy::query()
            ->with('npc')
            ->where('mapping_version_id', $mappingVersion->id)
            ->whereNotNull('floor_id')
            ->whereNotNull('npc_id')
            ->whereNull('teeming')
            ->whereNull('seasonal_type')
            ->orderBy('id')
            ->get();

        [$packed, $unpacked] = $enemies->partition(static fn(Enemy $enemy) => $enemy->enemy_pack_id !== null);

        return $packed->groupBy('enemy_pack_id')
            ->sortKeys()
            ->values()
            ->concat($unpacked->map(static fn(Enemy $enemy) => collect([$enemy])));
    }

    /**
     * The forces a pulled kill zone enemy key adds to a route, computed the way DungeonRoute::getEnemyForces()
     * does for a route without teeming or shrouded: every enemy sharing the key counts, once per route.
     *
     * @return Collection<string, int>
     */
    private function getEnemyForcesByKey(MappingVersion $mappingVersion): Collection
    {
        $npcEnemyForces = NpcEnemyForces::query()
            ->where('mapping_version_id', $mappingVersion->id)
            ->pluck('enemy_forces', 'npc_id');

        $result = collect();
        foreach (Enemy::query()->where('mapping_version_id', $mappingVersion->id)->get() as $enemy) {
            $key = $this->getEnemyKey($enemy);
            if ($key === null) {
                continue;
            }

            $result->put($key, $result->get($key, 0) + (int)($enemy->enemy_forces_override ?? $npcEnemyForces->get($enemy->mdt_npc_id ?? $enemy->npc_id, 0)));
        }

        return $result;
    }

    private function getEnemyKey(Enemy $enemy): ?string
    {
        $npcId = $enemy->mdt_npc_id ?? $enemy->npc_id;

        return $npcId === null || $enemy->mdt_id === null ? null : sprintf('%d-%d', $npcId, $enemy->mdt_id);
    }

    /**
     * @throws TestDungeonRouteGeneratorException
     */
    private function ensureAvailable(): void
    {
        if (!$this->isAvailable()) {
            throw new TestDungeonRouteGeneratorException(
                sprintf('Generating test routes is not available on app type %s', config('app.type')),
            );
        }
    }
}
