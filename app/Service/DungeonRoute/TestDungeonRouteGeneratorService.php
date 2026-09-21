<?php

namespace App\Service\DungeonRoute;

use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Enemy;
use App\Models\Faction;
use App\Models\KillZone\KillZone;
use App\Models\KillZone\KillZoneEnemy;
use App\Models\PublishedState;
use App\Models\Tags\Tag;
use App\Models\Tags\TagCategory;
use App\Models\User;
use App\Service\DungeonRoute\Exceptions\TestDungeonRouteGeneratorException;
use App\Service\Season\SeasonServiceInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

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

    public function __construct(
        private readonly SeasonServiceInterface $seasonService,
    ) {
    }

    public function isAvailable(): bool
    {
        return in_array(config('app.type'), self::ALLOWED_APP_TYPES, true);
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

        $pullCandidates = $this->getPullCandidates($mappingVersion->id);
        if ($pullCandidates->isEmpty()) {
            throw new TestDungeonRouteGeneratorException(
                sprintf('Dungeon %s has no enemies on mapping version %d', $dungeon->key, $mappingVersion->id),
            );
        }

        $season         = $dungeon->getActiveSeason($this->seasonService);
        $keyLevelMin    = $season === null ? (int)config('keystoneguru.keystone.levels.default_min') : $season->key_level_min;
        $keyLevelMax    = $season === null ? (int)config('keystoneguru.keystone.levels.default_max') : $season->key_level_max;
        $forcesRequired = $mappingVersion->enemy_forces_required;

        $result = collect();
        for ($i = 1; $i <= $count; $i++) {
            $levelMin = random_int($keyLevelMin, max($keyLevelMin, $keyLevelMax - 5));

            $dungeonRoute = DungeonRoute::create([
                'public_key'         => DungeonRoute::generateRandomPublicKey(),
                'author_id'          => $author->id,
                'dungeon_id'         => $dungeon->id,
                'mapping_version_id' => $mappingVersion->id,
                'season_id'          => $season?->id,
                'faction_id'         => Faction::ALL[Faction::FACTION_UNSPECIFIED],
                'published_state_id' => $publishedStateId,
                'title'              => sprintf('Test route %d - %s', $i, __($dungeon->name, [], 'en_US')),
                'description'        => '',
                'level_min'          => $levelMin,
                'level_max'          => min($keyLevelMax, $levelMin + random_int(0, 5)),
                'expires_at'         => null,
                'published_at'       => $publishedStateId === PublishedState::ALL[PublishedState::UNPUBLISHED] ? null : Carbon::now(),
            ]);

            $targetForces = (int)ceil($forcesRequired * random_int(self::TARGET_FORCES_PERCENTAGE_MIN, self::TARGET_FORCES_PERCENTAGE_MAX) / 100);

            $dungeonRoute->update(['enemy_forces' => $this->createPulls($dungeonRoute, $pullCandidates, $targetForces)]);

            Tag::create([
                'context_id'      => $author->id,
                'context_class'   => User::class,
                'tag_category_id' => TagCategory::ALL[TagCategory::DUNGEON_ROUTE_PERSONAL],
                'model_id'        => $dungeonRoute->id,
                'model_class'     => DungeonRoute::class,
                'name'            => self::TAG_NAME,
                'color'           => null,
            ]);

            $result->push($dungeonRoute);
        }

        return $result;
    }

    public function deleteGenerated(int $limit): array
    {
        $this->ensureAvailable();

        $dungeonRoutes = $this->generatedQuery()->orderBy('id')->limit($limit)->get();
        foreach ($dungeonRoutes as $dungeonRoute) {
            $dungeonRoute->delete();
        }

        return [
            'deleted'   => $dungeonRoutes->count(),
            'remaining' => $this->countGenerated(),
        ];
    }

    public function countGenerated(): int
    {
        return $this->generatedQuery()->count();
    }

    /**
     * @return Builder<DungeonRoute>
     */
    private function generatedQuery(): Builder
    {
        return DungeonRoute::query()->whereIn('id', Tag::query()
            ->select('model_id')
            ->where('model_class', DungeonRoute::class)
            ->where('tag_category_id', TagCategory::ALL[TagCategory::DUNGEON_ROUTE_PERSONAL])
            ->where('name', self::TAG_NAME));
    }

    /**
     * Adds pulls from the candidates, in order, until the route reaches the target enemy forces or the
     * candidates run out, then adds every boss that was not pulled yet as a pull of its own.
     *
     * @param  Collection<int, Collection<int, Enemy>> $pullCandidates
     * @return int                                     the route's resulting enemy forces
     */
    private function createPulls(DungeonRoute $dungeonRoute, Collection $pullCandidates, int $targetForces): int
    {
        // Skipping a random part of the candidates on the first pass keeps routes of the same dungeon apart
        [$skipped, $firstPass] = $pullCandidates->partition(static fn() => random_int(1, 100) <= self::SKIP_PULL_CANDIDATE_PERCENTAGE);

        $pulledEnemyIds = [];
        $index          = 1;
        foreach ($firstPass->concat($skipped) as $enemies) {
            $this->createPull($dungeonRoute, $enemies, $index++);
            array_push($pulledEnemyIds, ...$enemies->pluck('id'));

            if ($dungeonRoute->getEnemyForces() >= $targetForces) {
                break;
            }
        }

        $remainingBosses = $pullCandidates->flatten(1)
            ->filter(static fn(Enemy $enemy) => $enemy->npc?->isBoss() && !in_array($enemy->id, $pulledEnemyIds, true));
        foreach ($remainingBosses as $boss) {
            $this->createPull($dungeonRoute, collect([$boss]), $index++);
        }

        return $dungeonRoute->getEnemyForces();
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
     * Every pack as one pull in pack order, followed by every enemy outside a pack as a pull of its own,
     * so a dungeon whose packs fall short of the required forces can still be completed.
     *
     * @return Collection<int, Collection<int, Enemy>>
     */
    private function getPullCandidates(int $mappingVersionId): Collection
    {
        $enemies = Enemy::query()
            ->with('npc')
            ->where('mapping_version_id', $mappingVersionId)
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
