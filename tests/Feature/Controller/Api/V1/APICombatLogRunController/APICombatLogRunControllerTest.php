<?php

namespace Tests\Feature\Controller\Api\V1\APICombatLogRunController;

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
#[Group('CombatLog')]
#[Group('APICombatLogRun')]
final class APICombatLogRunControllerTest extends PublicTestCase
{
    private const int RUN_ID = 123456789;

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

    /**
     * @throws Exception
     */
    #[Test]
    public function segments_givenRunWithSegments_shouldReturnDownloadUrls(): void
    {
        // Arrange
        $this->actingAsAdmin();

        $season = Season::query()->firstOrFail();

        $raiderIOApiService = $this->createMockPublic(RaiderIOApiServiceInterface::class);
        $raiderIOApiService->expects($this->once())
            ->method('getCombatLogSegmentsForRun')
            ->with($this->callback(static fn(Season $passed): bool => $passed->id === $season->id), self::RUN_ID)
            ->willReturn(new CombatLogSegmentsResponse(1, [
                new CombatLogSegment(1, 'log', 'https://example.com/segment-1.log'),
            ]));
        app()->instance(RaiderIOApiServiceInterface::class, $raiderIOApiService);

        // Act
        $response = $this->getJson(route('api.v1.combatlog.run.segments', [
            'season' => $season->id,
            'runId'  => self::RUN_ID,
        ]));

        // Assert
        $response->assertOk();
        $response->assertExactJson([
            'segments' => [
                ['id' => 1, 'type' => 'log', 'downloadUrl' => 'https://example.com/segment-1.log'],
            ],
        ]);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function segments_givenNoSegmentsAvailable_shouldReturnNotFound(): void
    {
        // Arrange
        $this->actingAsAdmin();

        $season = Season::query()->firstOrFail();

        $raiderIOApiService = $this->createMockPublic(RaiderIOApiServiceInterface::class);
        $raiderIOApiService->expects($this->once())
            ->method('getCombatLogSegmentsForRun')
            ->willReturn(null);
        app()->instance(RaiderIOApiServiceInterface::class, $raiderIOApiService);

        // Act
        $response = $this->getJson(route('api.v1.combatlog.run.segments', [
            'season' => $season->id,
            'runId'  => self::RUN_ID,
        ]));

        // Assert
        $response->assertStatus(StatusCode::NOT_FOUND);
        $response->assertJsonStructure(['error']);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function segments_givenUnknownSeason_shouldReturnUnprocessable(): void
    {
        // Arrange
        $this->actingAsAdmin();

        $unknownSeasonId = (int)Season::query()->max('id') + 1000;

        $raiderIOApiService = $this->createMockPublic(RaiderIOApiServiceInterface::class);
        $raiderIOApiService->expects($this->never())->method('getCombatLogSegmentsForRun');
        app()->instance(RaiderIOApiServiceInterface::class, $raiderIOApiService);

        // Act
        $response = $this->getJson(route('api.v1.combatlog.run.segments', [
            'season' => $unknownSeasonId,
            'runId'  => self::RUN_ID,
        ]));

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonPath('success', false);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function segments_givenAiAgent_shouldReturnDownloadUrls(): void
    {
        // Arrange
        /** @var User $aiAgent */
        $aiAgent = User::factory()->create();

        try {
            $aiAgent->addRole(Role::ROLE_AI_AGENT);
            $this->actingAs($aiAgent);

            $season = Season::query()->firstOrFail();

            $raiderIOApiService = $this->createMockPublic(RaiderIOApiServiceInterface::class);
            $raiderIOApiService->expects($this->once())
                ->method('getCombatLogSegmentsForRun')
                ->willReturn(new CombatLogSegmentsResponse(1, [new CombatLogSegment(1, 'log', 'https://example.com/segment-1.log')]));
            app()->instance(RaiderIOApiServiceInterface::class, $raiderIOApiService);

            // Act
            $response = $this->getJson(route('api.v1.combatlog.run.segments', [
                'season' => $season->id,
                'runId'  => self::RUN_ID,
            ]));

            // Assert
            $response->assertOk();
        } finally {
            $aiAgent->delete();
        }
    }

    #[Test]
    public function segments_givenAuthenticatedNonAdmin_shouldReturnForbidden(): void
    {
        // Arrange
        /** @var User $nonAdmin */
        $nonAdmin = User::factory()->create();

        try {
            $this->actingAs($nonAdmin);

            $season = Season::query()->firstOrFail();

            // Act
            $response = $this->getJson(route('api.v1.combatlog.run.segments', [
                'season' => $season->id,
                'runId'  => self::RUN_ID,
            ]));

            // Assert
            $response->assertStatus(StatusCode::FORBIDDEN);
        } finally {
            $nonAdmin->delete();
        }
    }

    #[Test]
    public function segments_givenUnauthenticated_shouldReturnForbidden(): void
    {
        // Arrange
        $season = Season::query()->firstOrFail();

        // Act
        $response = $this->getJson(route('api.v1.combatlog.run.segments', [
            'season' => $season->id,
            'runId'  => self::RUN_ID,
        ]));

        // Assert
        $response->assertStatus(StatusCode::FORBIDDEN);
    }
}
