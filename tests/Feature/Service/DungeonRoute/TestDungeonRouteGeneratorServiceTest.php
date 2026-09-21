<?php

namespace Tests\Feature\Service\DungeonRoute;

use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Enemy;
use App\Models\KillZone\KillZoneEnemy;
use App\Models\Mapping\MappingVersion;
use App\Models\Npc\NpcClassification;
use App\Models\PublishedState;
use App\Models\Tags\Tag;
use App\Models\User;
use App\Service\DungeonRoute\Exceptions\TestDungeonRouteGeneratorException;
use App\Service\DungeonRoute\TestDungeonRouteGeneratorServiceInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
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
            resolve:       fn(Dungeon $dungeon, MappingVersion $mappingVersion) => $this->sumEnemyForces($mappingVersion, null) >= $mappingVersion->enemy_forces_required
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
                && $this->sumEnemyForces($mappingVersion, null) >= $mappingVersion->enemy_forces_required ? true : null,
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
    public function isAvailable_givenAppType_returnsWhetherGenerationIsAllowed(): void
    {
        // Arrange
        $availability = [];

        // Act
        foreach (['local', 'staging', 'production', 'mapping'] as $appType) {
            config(['app.type' => $appType]);
            $availability[$appType] = $this->service->isAvailable();
        }

        // Assert
        $this->assertSame([
            'local'      => true,
            'staging'    => true,
            'production' => false,
            'mapping'    => false,
        ], $availability);
    }

    #[Test]
    public function deleteGenerated_givenGeneratedAndRegularRoutes_deletesOnlyGeneratedRoutes(): void
    {
        // Arrange
        $dungeon       = $this->getDungeonWithCurrentMappingVersionWithEnemies();
        $dungeonRoutes = collect();
        $regularRoute  = null;

        try {
            $regularRoute  = DungeonRoute::factory()->create(['dungeon_id' => $dungeon->id, 'expires_at' => null]);
            $dungeonRoutes = $this->service->generate($dungeon, User::findOrFail(1), 3, PublishedState::ALL[PublishedState::UNPUBLISHED]);
            $countBefore   = $this->service->countGenerated();

            // Act
            $firstBatch  = $this->service->deleteGenerated(2);
            $secondBatch = $this->service->deleteGenerated(2);

            // Assert
            $this->assertSame(2, $firstBatch['deleted']);
            $this->assertSame($countBefore - 2, $firstBatch['remaining']);
            $this->assertSame($countBefore - 2 - $secondBatch['deleted'], $secondBatch['remaining']);
            $this->assertFalse(DungeonRoute::query()->whereIn('id', $dungeonRoutes->pluck('id'))->exists(), 'Generated routes must be deleted');
            $this->assertSame(0, Tag::query()->whereIn('model_id', $dungeonRoutes->pluck('id'))->where('model_class', DungeonRoute::class)->count(), 'The marker tags must be deleted with the routes');
            $this->assertTrue(DungeonRoute::query()->whereKey($regularRoute->id)->exists(), 'A regular route must never be deleted');
        } finally {
            DungeonRoute::query()->whereIn('id', $dungeonRoutes->pluck('id'))->get()->each->delete();
            $regularRoute?->delete();
        }
    }

    private function assertGeneratedRoute(DungeonRoute $dungeonRoute, MappingVersion $mappingVersion): void
    {
        $dungeonRoute->refresh();

        $this->assertSame($mappingVersion->id, $dungeonRoute->mapping_version_id);
        $this->assertNull($dungeonRoute->expires_at, 'A generated route must not be a sandbox route');
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
     * @param bool|null $packed true for enemies in a pack only, null for every enemy
     */
    private function sumEnemyForces(MappingVersion $mappingVersion, ?bool $packed): int
    {
        return (int)Enemy::query()
            ->join('npc_enemy_forces', static fn(JoinClause $join) => $join
                ->on('npc_enemy_forces.npc_id', '=', 'enemies.npc_id')
                ->where('npc_enemy_forces.mapping_version_id', $mappingVersion->id))
            ->where('enemies.mapping_version_id', $mappingVersion->id)
            ->whereNotNull('enemies.floor_id')
            ->whereNull('enemies.teeming')
            ->whereNull('enemies.seasonal_type')
            ->when($packed === true, static fn(Builder $query) => $query->whereNotNull('enemies.enemy_pack_id'))
            ->sum('npc_enemy_forces.enemy_forces');
    }
}
