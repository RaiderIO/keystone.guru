<?php

namespace Tests\Feature\Console\Commands\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\User;
use App\Service\DungeonRoute\TestDungeonRouteGeneratorServiceInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Attributes\SlowTest;
use Tests\Feature\Traits\ProvidesDungeon;
use Tests\TestCases\PublicTestCase;

#[Group('Console')]
#[Group('DungeonRoute')]
#[SlowTest]
final class GenerateTestRoutesTest extends PublicTestCase
{
    use ProvidesDungeon;

    private int $maxDungeonRouteIdBefore;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        config(['app.type' => 'local']);
        $this->be(User::findOrFail(1));
        $this->maxDungeonRouteIdBefore = (int)DungeonRoute::query()->max('id');
    }

    #[\Override]
    protected function tearDown(): void
    {
        try {
            DungeonRoute::query()->where('id', '>', $this->maxDungeonRouteIdBefore)->get()->each->delete();
        } finally {
            parent::tearDown();
        }
    }

    #[Test]
    public function handle_givenDungeonAndCount_generatesThatManyRoutes(): void
    {
        // Arrange
        $dungeon = $this->getDungeonWithCurrentMappingVersionWithEnemies();

        // Act
        $this->artisan('dungeonroute:generatetest', ['--dungeon' => [$dungeon->key], '--count' => 2])
            ->assertSuccessful();

        // Assert
        $this->assertSame(2, DungeonRoute::query()
            ->where('id', '>', $this->maxDungeonRouteIdBefore)
            ->where('dungeon_id', $dungeon->id)
            ->count());
    }

    #[Test]
    public function handle_givenCountAboveMax_failsWithoutCreatingRoutes(): void
    {
        // Arrange
        $dungeon = $this->getDungeonWithCurrentMappingVersionWithEnemies();

        // Act
        $this->artisan('dungeonroute:generatetest', [
            '--dungeon' => [$dungeon->key],
            '--count'   => TestDungeonRouteGeneratorServiceInterface::MAX_ROUTES_PER_DUNGEON + 1,
        ])->assertFailed();

        // Assert
        $this->assertFalse(DungeonRoute::query()->where('id', '>', $this->maxDungeonRouteIdBefore)->exists());
    }

    #[Test]
    public function handle_givenNoDungeonOrSeason_fails(): void
    {
        // Arrange

        // Act
        $result = $this->artisan('dungeonroute:generatetest');

        // Assert
        $result->assertFailed();
    }

    #[Test]
    public function handle_givenDeleteWithAuthor_deletesOnlyThatAuthorsGeneratedRoutes(): void
    {
        // Arrange
        $dungeon = $this->getDungeonWithCurrentMappingVersionWithEnemies();
        $author  = null;

        try {
            $author = User::factory()->create();
            $this->artisan('dungeonroute:generatetest', ['--dungeon' => [$dungeon->key], '--count' => 2, '--author' => $author->id])->assertSuccessful();
            $this->artisan('dungeonroute:generatetest', ['--dungeon' => [$dungeon->key], '--count' => 1])->assertSuccessful();

            // Act
            $this->artisan('dungeonroute:generatetest', ['--delete' => true, '--author' => $author->id])->assertSuccessful();

            // Assert
            $service = app(TestDungeonRouteGeneratorServiceInterface::class);
            $this->assertSame(0, $service->countGenerated($author));
            $this->assertSame(1, DungeonRoute::query()->where('id', '>', $this->maxDungeonRouteIdBefore)->where('author_id', 1)->count(), 'Another author\'s generated route must survive');
        } finally {
            DungeonRoute::query()->where('id', '>', $this->maxDungeonRouteIdBefore)->get()->each->delete();
            $author?->delete();
        }
    }
}
