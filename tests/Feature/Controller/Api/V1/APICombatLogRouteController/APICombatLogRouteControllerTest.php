<?php

namespace Tests\Feature\Controller\Api\V1\APICombatLogRouteController;

use App\Models\CombatLog\ChallengeModeRun;
use App\Models\CombatLog\ChallengeModeRunData;
use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Laratrust\Role;
use App\Models\Mapping\MappingVersion;
use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Teapot\StatusCode;
use Tests\Feature\Traits\ProvidesDungeon;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('API')]
#[Group('CombatLog')]
#[Group('APICombatLogRoute')]
final class APICombatLogRouteControllerTest extends PublicTestCase
{
    use ProvidesDungeon;

    private Dungeon $dungeon;

    private MappingVersion $mappingVersion;

    /** @var array<int, int> */
    private array $createdDungeonRouteIds = [];

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        [$this->dungeon, $this->mappingVersion] = $this->findDungeon();
    }

    #[\Override]
    protected function tearDown(): void
    {
        try {
            $runIds = ChallengeModeRun::query()->whereIn('dungeon_route_id', $this->createdDungeonRouteIds)->pluck('id');
            ChallengeModeRunData::query()->whereIn('challenge_mode_run_id', $runIds)->delete();
            ChallengeModeRun::query()->whereIn('id', $runIds)->delete();
            DungeonRoute::query()->whereIn('id', $this->createdDungeonRouteIds)->delete();
        } finally {
            parent::tearDown();
        }
    }

    #[Test]
    public function postBody_givenRouteWithRunData_returnsStoredJsonVerbatim(): void
    {
        // Arrange
        $this->actingAsAdmin();

        $postBody     = '{"metadata":{"version":2},"npcs":[{"npcId":1,"coord":{"x":1.5,"y":2.5}}]}';
        $dungeonRoute = $this->createCombatLogRoute($postBody);

        // Act
        $response = $this->get(route('api.v1.combatlog.route.post_body', ['dungeonRoute' => $dungeonRoute->public_key]));

        // Assert
        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/json');
        $this->assertSame($postBody, $response->getContent());
    }

    #[Test]
    public function postBody_givenRouteWithoutRunData_returnsNotFound(): void
    {
        // Arrange
        $this->actingAsAdmin();

        $dungeonRoute = $this->createCombatLogRoute(null);

        // Act
        $response = $this->getJson(route('api.v1.combatlog.route.post_body', ['dungeonRoute' => $dungeonRoute->public_key]));

        // Assert
        $response->assertStatus(StatusCode::NOT_FOUND);
        $response->assertJsonStructure(['error']);
    }

    #[Test]
    public function postBody_givenAuthenticatedNonAdmin_returnsForbidden(): void
    {
        // Arrange
        /** @var User $nonAdmin */
        $nonAdmin = User::factory()->create();

        try {
            $this->actingAs($nonAdmin);
            $dungeonRoute = $this->createCombatLogRoute('{}');

            // Act
            $response = $this->getJson(route('api.v1.combatlog.route.post_body', ['dungeonRoute' => $dungeonRoute->public_key]));

            // Assert
            $response->assertStatus(StatusCode::FORBIDDEN);
        } finally {
            $nonAdmin->delete();
        }
    }

    private function actingAsAdmin(): void
    {
        /** @var User $admin */
        $admin = User::findOrFail(1);
        $this->assertTrue($admin->hasRole(Role::ROLE_ADMIN), 'User id=1 must have the admin role for this test (seed the database).');
        $this->actingAs($admin);
    }

    /**
     * A dungeon route with a challenge mode run; the run's data row carries $postBody (or none when null).
     */
    private function createCombatLogRoute(?string $postBody): DungeonRoute
    {
        $dungeonRoute = DungeonRoute::factory()->create([
            'dungeon_id'         => $this->dungeon->id,
            'mapping_version_id' => $this->mappingVersion->id,
        ]);
        $this->createdDungeonRouteIds[] = $dungeonRoute->id;

        $challengeModeRun = ChallengeModeRun::create([
            'dungeon_id'       => $this->dungeon->id,
            'dungeon_route_id' => $dungeonRoute->id,
            'level'            => 10,
            'success'          => 1,
            'total_time_ms'    => 1000,
            'duplicate'        => 0,
        ]);

        if ($postBody !== null) {
            ChallengeModeRunData::create([
                'challenge_mode_run_id' => $challengeModeRun->id,
                'run_id'                => sprintf('test-run-%d', $dungeonRoute->id),
                'correlation_id'        => sprintf('test-correlation-%d', $dungeonRoute->id),
                'post_body'             => $postBody,
                'processed'             => 1,
            ]);
        }

        return $dungeonRoute;
    }
}
