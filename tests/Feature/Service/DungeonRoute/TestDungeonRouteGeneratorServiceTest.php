<?php

namespace Tests\Feature\Service\DungeonRoute;

use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Enemy;
use App\Models\KillZone\KillZoneEnemy;
use App\Models\Mapping\MappingVersion;
use App\Models\Npc\NpcClassification;
use App\Models\Npc\NpcEnemyForces;
use App\Models\PublishedState;
use App\Models\Tags\Tag;
use App\Models\Tags\TagCategory;
use App\Models\User;
use App\Service\DungeonRoute\Exceptions\TestDungeonRouteGeneratorException;
use App\Service\DungeonRoute\TestDungeonRouteGeneratorServiceInterface;
use Illuminate\Database\Eloquent\Builder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Attributes\SlowTest;
use Tests\Feature\Traits\ProvidesDungeon;
use Tests\TestCases\PublicTestCase;

#[Group('Service')]
#[Group('DungeonRoute')]
#[SlowTest]
final class TestDungeonRouteGeneratorServiceTest extends PublicTestCase
{
    use ProvidesDungeon;

    private TestDungeonRouteGeneratorServiceInterface $service;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        config(['app.type' => 'local']);
        $this->be(User::findOrFail(1));
        $this->service = app(TestDungeonRouteGeneratorServiceInterface::class);
    }

    #[Test]
    public function generate_givenDungeonWhoseEnemiesReachRequiredForces_createsRoutesThatReachForcesAndKillEveryBoss(): void
    {
        // Arrange
        [$dungeon, $mappingVersion] = $this->findDungeon(
            challengeMode: true,
            minEnemies:    1,
            resolve:       fn(Dungeon $dungeon, MappingVersion $mappingVersion) => $this->sumEnemyForces($mappingVersion, false) >= $mappingVersion->enemy_forces_required
                && $mappingVersion->enemy_forces_required > 0 ? true : null,
        );
        $dungeonRoutes = collect();

        try {
            // Act
            $dungeonRoutes = $this->service->generate($dungeon, User::findOrFail(1), 2, PublishedState::ALL[PublishedState::WORLD]);

            // Assert
            $this->assertCount(2, $dungeonRoutes);
            foreach ($dungeonRoutes as $dungeonRoute) {
                $this->assertGeneratedRoute($dungeonRoute, $mappingVersion);
            }
        } finally {
            $dungeonRoutes->each->delete();
        }
    }

    #[Test]
    public function generate_givenDungeonWhosePacksFallShortOfRequiredForces_reachesRequiredForcesWithUnpackedEnemies(): void
    {
        // Arrange
        [$dungeon, $mappingVersion] = $this->findDungeon(
            challengeMode: true,
            minEnemies:    1,
            resolve:       fn(Dungeon $dungeon, MappingVersion $mappingVersion) => $this->sumEnemyForces($mappingVersion, true) < $mappingVersion->enemy_forces_required
                && $this->sumEnemyForces($mappingVersion, false) >= $mappingVersion->enemy_forces_required ? true : null,
        );
        $dungeonRoutes = collect();

        try {
            // Act
            $dungeonRoutes = $this->service->generate($dungeon, User::findOrFail(1), 1, PublishedState::ALL[PublishedState::WORLD]);

            // Assert
            $this->assertGeneratedRoute($dungeonRoutes->first(), $mappingVersion);
        } finally {
            $dungeonRoutes->each->delete();
        }
    }

    #[Test]
    #[DataProvider('generate_givenCountOutsideRange_throwsException_dataProvider')]
    public function generate_givenCountOutsideRange_throwsException(int $count): void
    {
        // Arrange
        $dungeon = $this->getDungeonWithCurrentMappingVersionWithEnemies();
        $before  = DungeonRoute::query()->count();

        // Assert
        $this->expectException(TestDungeonRouteGeneratorException::class);

        try {
            // Act
            $this->service->generate($dungeon, User::findOrFail(1), $count, PublishedState::ALL[PublishedState::WORLD]);
        } finally {
            $this->assertSame($before, DungeonRoute::query()->count());
        }
    }

    /**
     * @return array<string, array{int}>
     */
    public static function generate_givenCountOutsideRange_throwsException_dataProvider(): array
    {
        return [
            'zero'      => [0],
            'above max' => [TestDungeonRouteGeneratorServiceInterface::MAX_ROUTES_PER_DUNGEON + 1],
        ];
    }

    #[Test]
    public function generate_givenProductionAppType_throwsException(): void
    {
        // Arrange
        config(['app.type' => 'production']);
        $dungeon = $this->getDungeonWithCurrentMappingVersionWithEnemies();
        $before  = DungeonRoute::query()->count();

        // Assert
        $this->expectException(TestDungeonRouteGeneratorException::class);

        try {
            // Act
            $this->service->generate($dungeon, User::findOrFail(1), 1, PublishedState::ALL[PublishedState::WORLD]);
        } finally {
            $this->assertSame($before, DungeonRoute::query()->count());
        }
    }

    #[Test]
    #[DataProvider('isAvailable_givenAppTypeAndEnv_returnsWhetherGenerationIsAllowed_dataProvider')]
    public function isAvailable_givenAppTypeAndEnv_returnsWhetherGenerationIsAllowed(string $appType, string $appEnv, bool $expected): void
    {
        // Arrange
        config(['app.type' => $appType, 'app.env' => $appEnv]);

        // Act
        $result = $this->service->isAvailable();

        // Assert
        $this->assertSame($expected, $result);
    }

    /**
     * @return array<string, array{string, string, bool}>
     */
    public static function isAvailable_givenAppTypeAndEnv_returnsWhetherGenerationIsAllowed_dataProvider(): array
    {
        return [
            'local'                                  => ['local', 'local', true],
            'staging runs with a production APP_ENV' => ['staging', 'production', true],
            'production'                             => ['production', 'production', false],
            'mapping'                                => ['mapping', 'local', false],
            'unset APP_TYPE on a production APP_ENV' => ['local', 'production', false],
        ];
    }

    #[Test]
    public function deleteGenerated_givenGeneratedAndRegularRoutes_deletesOnlyTheAuthorsGeneratedRoutes(): void
    {
        // Arrange
        $dungeon       = $this->getDungeonWithCurrentMappingVersionWithEnemies();
        $author        = null;
        $otherUser     = null;
        $dungeonRoutes = collect();
        $regularRoute  = null;
        $taggedRoute   = null;

        try {
            $author       = User::factory()->create();
            $otherUser    = User::factory()->create();
            $regularRoute = DungeonRoute::factory()->create(['author_id' => $author->id, 'dungeon_id' => $dungeon->id, 'expires_at' => null]);
            $taggedRoute  = DungeonRoute::factory()->create(['author_id' => $author->id, 'dungeon_id' => $dungeon->id, 'expires_at' => null]);
            // Another user's personal tag with the marker name must not turn the route into a generated one
            Tag::create([
                'context_id'      => $otherUser->id,
                'context_class'   => User::class,
                'tag_category_id' => TagCategory::ALL[TagCategory::DUNGEON_ROUTE_PERSONAL],
                'model_id'        => $taggedRoute->id,
                'model_class'     => DungeonRoute::class,
                'name'            => TestDungeonRouteGeneratorServiceInterface::TAG_NAME,
            ]);
            $dungeonRoutes = $this->service->generate($dungeon, $author, 3, PublishedState::ALL[PublishedState::UNPUBLISHED]);

            // Act
            $firstBatch  = $this->service->deleteGenerated(2, $author);
            $secondBatch = $this->service->deleteGenerated(2, $author);

            // Assert
            $this->assertSame(['deleted' => 2, 'remaining' => 1], $firstBatch);
            $this->assertSame(['deleted' => 1, 'remaining' => 0], $secondBatch);
            $this->assertFalse(DungeonRoute::query()->whereIn('id', $dungeonRoutes->pluck('id'))->exists(), 'Generated routes must be deleted');
            $this->assertSame(0, Tag::query()->whereIn('model_id', $dungeonRoutes->pluck('id'))->where('model_class', DungeonRoute::class)->count(), 'The marker tags must be deleted with the routes');
            $this->assertTrue(DungeonRoute::query()->whereKey($regularRoute->id)->exists(), 'A regular route must never be deleted');
            $this->assertTrue(DungeonRoute::query()->whereKey($taggedRoute->id)->exists(), 'A route tagged by someone other than its author must never be deleted');
        } finally {
            DungeonRoute::query()->whereIn('id', $dungeonRoutes->pluck('id'))->get()->each->delete();
            $regularRoute?->delete();
            $taggedRoute?->delete();
            $author?->delete();
            $otherUser?->delete();
        }
    }

    private function assertGeneratedRoute(DungeonRoute $dungeonRoute, MappingVersion $mappingVersion): void
    {
        $dungeonRoute->refresh();

        $this->assertSame($mappingVersion->id, $dungeonRoute->mapping_version_id);
        $this->assertNull($dungeonRoute->expires_at, 'A generated route must not be a sandbox route');
        $this->assertTrue($dungeonRoute->published_at->isAfter(now()->subHour()), 'A generated route must be published now, not at the column default');
        $this->assertGreaterThanOrEqual($mappingVersion->enemy_forces_required, $dungeonRoute->enemy_forces);
        $this->assertSame($dungeonRoute->getEnemyForces(), $dungeonRoute->enemy_forces);

        $bossEnemyIds = Enemy::query()
            ->where('mapping_version_id', $mappingVersion->id)
            ->whereNotNull('floor_id')
            ->whereNull('teeming')
            ->whereNull('seasonal_type')
            ->whereHas('npc', static fn(Builder $query) => $query->whereIn('classification_id', [
                NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_BOSS],
                NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_FINAL_BOSS],
            ]))
            ->pluck('id');
        $killedEnemyIds = KillZoneEnemy::query()
            ->whereIn('kill_zone_id', $dungeonRoute->killZones()->pluck('id'))
            ->pluck('enemy_id');

        $this->assertEmpty($bossEnemyIds->diff($killedEnemyIds), 'Every boss must be killed');

        $this->assertTrue(
            Tag::query()->where('model_id', $dungeonRoute->id)->where('model_class', DungeonRoute::class)
                ->where('name', TestDungeonRouteGeneratorServiceInterface::TAG_NAME)->exists(),
            'A generated route must carry the marker tag',
        );
    }

    /**
     * Total forces the generator can reach from its pull candidates, counted the way
     * DungeonRoute::getEnemyForces() counts them: once per (npc, mdt id) key, at the rounded average forces
     * of the enemies sharing the key.
     *
     * @param bool $packedOnly only count candidates that belong to a pack
     */
    private function sumEnemyForces(MappingVersion $mappingVersion, bool $packedOnly): int
    {
        $enemies        = Enemy::query()->where('mapping_version_id', $mappingVersion->id)->get();
        $npcEnemyForces = NpcEnemyForces::query()->where('mapping_version_id', $mappingVersion->id)->pluck('enemy_forces', 'npc_id');
        $keyOf          = static fn(Enemy $enemy) => $enemy->mdt_id === null ? null : sprintf('%d-%d', $enemy->mdt_npc_id ?? $enemy->npc_id, $enemy->mdt_id);

        $forcesByKey = $enemies->groupBy($keyOf)->map(static fn($sharingKey) => (int)round($sharingKey->avg(
            static fn(Enemy $enemy) => (int)($enemy->enemy_forces_override ?? $npcEnemyForces->get($enemy->mdt_npc_id ?? $enemy->npc_id, 0)),
        )));

        return (int)Enemy::query()
            ->where('mapping_version_id', $mappingVersion->id)
            ->whereNotNull('floor_id')
            ->whereNotNull('npc_id')
            ->whereNotNull('mdt_id')
            ->whereNull('teeming')
            ->whereNull('seasonal_type')
            ->when($packedOnly, static fn(Builder $query) => $query->whereNotNull('enemy_pack_id'))
            ->get()
            ->map($keyOf)
            ->unique()
            ->sum(static fn(string $key) => $forcesByKey->get($key, 0));
    }
}
