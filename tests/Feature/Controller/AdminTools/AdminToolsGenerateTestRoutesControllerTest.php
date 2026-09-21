<?php

namespace Tests\Feature\Controller\AdminTools;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Laratrust\Role;
use App\Models\PublishedState;
use App\Models\User;
use App\Service\DungeonRoute\TestDungeonRouteGeneratorServiceInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Attributes\SlowTest;
use Tests\Feature\Traits\ProvidesDungeon;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('AdminTools')]
#[SlowTest]
final class AdminToolsGenerateTestRoutesControllerTest extends PublicTestCase
{
    use ProvidesDungeon;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        config(['app.type' => 'local']);
        $this->be(User::findOrFail(1));
    }

    #[Test]
    public function index_givenAdmin_returnsOk(): void
    {
        // Arrange

        // Act
        $response = $this->get(route('admin.tools.dungeonroute.generatetestroutes'));

        // Assert
        $response->assertOk();
        $response->assertSee('id="generate_target"', false);
        $response->assertSee(sprintf('max="%d"', TestDungeonRouteGeneratorServiceInterface::MAX_ROUTES_PER_DUNGEON), false);
    }

    #[Test]
    public function index_givenProductionAppType_returnsNotFound(): void
    {
        // Arrange
        config(['app.type' => 'production']);

        // Act
        $response = $this->get(route('admin.tools.dungeonroute.generatetestroutes'));

        // Assert
        $response->assertNotFound();
    }

    #[Test]
    public function toolsList_givenAppType_showsLinkOnlyOutsideProduction(): void
    {
        // Arrange
        $url = route('admin.tools.dungeonroute.generatetestroutes');

        // Act
        $localResponse = $this->get(route('admin.tools'));
        config(['app.type' => 'production']);
        $productionResponse = $this->get(route('admin.tools'));

        // Assert
        $localResponse->assertSee($url);
        $productionResponse->assertOk();
        $productionResponse->assertDontSee($url);
    }

    #[Test]
    public function generate_givenValidRequest_returnsCreatedRoutesOwnedByTheAdmin(): void
    {
        // Arrange
        $dungeon    = $this->getDungeonWithCurrentMappingVersionWithEnemies();
        $publicKeys = [];

        try {
            // Act
            $response = $this->postJson(route('admin.tools.dungeonroute.generatetestroutes.generate'), [
                'dungeon_id'      => $dungeon->id,
                'count'           => 2,
                'published_state' => PublishedState::TEAM,
            ]);
            $publicKeys = $response->json('routes.*.public_key') ?? [];

            // Assert
            $response->assertOk();
            $response->assertJsonCount(2, 'routes');
            $response->assertJsonStructure(['dungeon', 'routes' => [['public_key', 'title', 'url', 'enemy_forces']], 'enemy_forces_required', 'generated_count']);
            $dungeonRoutes = DungeonRoute::query()->whereIn('public_key', $publicKeys)->get();
            $this->assertCount(2, $dungeonRoutes);
            foreach ($dungeonRoutes as $dungeonRoute) {
                $this->assertSame(1, $dungeonRoute->author_id);
                $this->assertSame($dungeon->id, $dungeonRoute->dungeon_id);
                $this->assertSame(PublishedState::ALL[PublishedState::TEAM], $dungeonRoute->published_state_id);
            }
        } finally {
            DungeonRoute::query()->whereIn('public_key', $publicKeys)->get()->each->delete();
        }
    }

    #[Test]
    public function generate_givenCountAboveMax_returnsValidationError(): void
    {
        // Arrange
        $dungeon = $this->getDungeonWithCurrentMappingVersionWithEnemies();
        $before  = DungeonRoute::query()->count();

        // Act
        $response = $this->postJson(route('admin.tools.dungeonroute.generatetestroutes.generate'), [
            'dungeon_id'      => $dungeon->id,
            'count'           => TestDungeonRouteGeneratorServiceInterface::MAX_ROUTES_PER_DUNGEON + 1,
            'published_state' => PublishedState::WORLD,
        ]);

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['count']);
        $this->assertSame($before, DungeonRoute::query()->count());
    }

    #[Test]
    public function generate_givenProductionAppType_returnsNotFound(): void
    {
        // Arrange
        config(['app.type' => 'production']);
        $dungeon = $this->getDungeonWithCurrentMappingVersionWithEnemies();
        $before  = DungeonRoute::query()->count();

        // Act
        $response = $this->postJson(route('admin.tools.dungeonroute.generatetestroutes.generate'), [
            'dungeon_id'      => $dungeon->id,
            'count'           => 1,
            'published_state' => PublishedState::WORLD,
        ]);

        // Assert
        $response->assertNotFound();
        $this->assertSame($before, DungeonRoute::query()->count());
    }

    #[Test]
    public function generate_givenNonAdmin_returnsForbidden(): void
    {
        // Arrange
        $dungeon = $this->getDungeonWithCurrentMappingVersionWithEnemies();
        $user    = null;

        try {
            $user = User::factory()->create();
            $user->addRole(Role::firstWhere('name', Role::ROLE_USER));
            $this->be($user);

            // Act
            $response = $this->postJson(route('admin.tools.dungeonroute.generatetestroutes.generate'), [
                'dungeon_id'      => $dungeon->id,
                'count'           => 1,
                'published_state' => PublishedState::WORLD,
            ]);

            // Assert
            $response->assertForbidden();
        } finally {
            $user?->delete();
        }
    }

    #[Test]
    public function deleteBatch_givenGeneratedRoutes_deletesThem(): void
    {
        // Arrange
        $dungeon       = $this->getDungeonWithCurrentMappingVersionWithEnemies();
        $dungeonRoutes = collect();

        try {
            $dungeonRoutes = app(TestDungeonRouteGeneratorServiceInterface::class)
                ->generate($dungeon, User::findOrFail(1), 1, PublishedState::ALL[PublishedState::UNPUBLISHED]);

            // Act
            $response = $this->postJson(route('admin.tools.dungeonroute.generatetestroutes.delete_batch'));

            // Assert
            $response->assertOk();
            $response->assertJsonStructure(['deleted', 'remaining']);
            $this->assertFalse(DungeonRoute::query()->whereKey($dungeonRoutes->first()->id)->exists());
        } finally {
            DungeonRoute::query()->whereIn('id', $dungeonRoutes->pluck('id'))->get()->each->delete();
        }
    }
}
