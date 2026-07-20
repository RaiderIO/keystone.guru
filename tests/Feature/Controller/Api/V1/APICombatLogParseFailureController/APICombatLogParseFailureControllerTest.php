<?php

namespace Tests\Feature\Controller\Api\V1\APICombatLogParseFailureController;

use App\Models\CombatLog\CombatLogParseFailure;
use App\Models\Laratrust\Role;
use App\Models\Season;
use App\Models\User;
use App\Service\RaiderIO\Dtos\CombatLogSegment;
use App\Service\RaiderIO\Dtos\CombatLogSegmentsResponse;
use App\Service\RaiderIO\RaiderIOApiServiceInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use Teapot\StatusCode;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('API')]
#[Group('APICombatLogParseFailure')]
final class APICombatLogParseFailureControllerTest extends PublicTestCase
{
    private function actingAsAdmin(): User
    {
        /** @var User $admin */
        $admin = User::findOrFail(1);
        $this->assertTrue(
            $admin->hasRole(Role::ROLE_ADMIN),
            'User id=1 must have the admin role for this test (seed the database).',
        );
        $this->actingAs($admin);

        return $admin;
    }

    #[Test]
    public function index_givenAuthenticatedAdmin_shouldReturnUnresolvedFailures(): void
    {
        // Arrange
        $this->actingAsAdmin();

        $unresolved = CombatLogParseFailure::factory()->create();
        $resolved   = CombatLogParseFailure::factory()->resolved()->create();

        try {
            // Act
            $response = $this->getJson(route('api.v1.combatlog.parsefailures.index'));

            // Assert
            $response->assertOk();
            /** @var array<int, array<string, mixed>> $data */
            $data = $response->json('data');
            $ids  = collect($data)->pluck('id');
            $this->assertTrue($ids->contains($unresolved->id));
            $this->assertFalse($ids->contains($resolved->id));
        } finally {
            $unresolved->delete();
            $resolved->delete();
        }
    }

    #[Test]
    public function index_givenAuthenticatedNonAdmin_shouldReturnForbidden(): void
    {
        // Arrange
        /** @var User $nonAdmin */
        $nonAdmin = User::factory()->create();

        try {
            $this->actingAs($nonAdmin);

            // Act
            $response = $this->getJson(route('api.v1.combatlog.parsefailures.index'));

            // Assert
            $response->assertStatus(StatusCode::FORBIDDEN);
        } finally {
            $nonAdmin->delete();
        }
    }

    #[Test]
    public function index_givenUnauthenticated_shouldReturnForbidden(): void
    {
        // Act
        $response = $this->getJson(route('api.v1.combatlog.parsefailures.index'));

        // Assert
        $response->assertStatus(StatusCode::FORBIDDEN);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function segments_givenFailureWithSegments_shouldReturnDownloadUrls(): void
    {
        // Arrange
        $this->actingAsAdmin();

        $season  = Season::query()->firstOrFail();
        $failure = CombatLogParseFailure::factory()->create(['season_id' => $season->id]);

        try {
            $segment = new CombatLogSegment(1, 'log', 'https://example.com/segment-1.log');

            $raiderIOApiService = $this->createMockPublic(RaiderIOApiServiceInterface::class);
            $raiderIOApiService->expects($this->once())
                ->method('getCombatLogSegmentsForRun')
                ->with($this->isInstanceOf(Season::class), $failure->run_id)
                ->willReturn(new CombatLogSegmentsResponse(1, [$segment]));
            app()->instance(RaiderIOApiServiceInterface::class, $raiderIOApiService);

            // Act
            $response = $this->getJson(route('api.v1.combatlog.parsefailures.segments', ['parseFailure' => $failure->id]));

            // Assert
            $response->assertOk();
            $response->assertExactJson([
                'segments' => [
                    ['id' => 1, 'type' => 'log', 'downloadUrl' => 'https://example.com/segment-1.log'],
                ],
            ]);
        } finally {
            $failure->delete();
        }
    }

    #[Test]
    public function segments_givenFailureWithoutSeason_shouldReturnUnprocessable(): void
    {
        // Arrange
        $this->actingAsAdmin();

        $failure = CombatLogParseFailure::factory()->create(['season_id' => null]);

        try {
            // Act
            $response = $this->getJson(route('api.v1.combatlog.parsefailures.segments', ['parseFailure' => $failure->id]));

            // Assert
            $response->assertUnprocessable();
            $response->assertJsonStructure(['error']);
        } finally {
            $failure->delete();
        }
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function segments_givenNoSegmentsAvailable_shouldReturnNotFound(): void
    {
        // Arrange
        $this->actingAsAdmin();

        $season  = Season::query()->firstOrFail();
        $failure = CombatLogParseFailure::factory()->create(['season_id' => $season->id]);

        try {
            $raiderIOApiService = $this->createMockPublic(RaiderIOApiServiceInterface::class);
            $raiderIOApiService->expects($this->once())
                ->method('getCombatLogSegmentsForRun')
                ->willReturn(null);
            app()->instance(RaiderIOApiServiceInterface::class, $raiderIOApiService);

            // Act
            $response = $this->getJson(route('api.v1.combatlog.parsefailures.segments', ['parseFailure' => $failure->id]));

            // Assert
            $response->assertStatus(StatusCode::NOT_FOUND);
            $response->assertJsonStructure(['error']);
        } finally {
            $failure->delete();
        }
    }
}
