<?php

namespace Tests\Feature\App\Service\RaiderIO\Dtos;

use App\Models\CombatLog\CombatLogEventDataType;
use App\Models\CombatLog\CombatLogEventEventType;
use App\Models\Dungeon;
use App\Models\Laratrust\Role;
use App\Models\User;
use App\Service\CombatLogEvent\Dtos\CombatLogEventFilter;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\RaiderIO\Dtos\RaiderIOHeatmapGridResponse;
use App\Service\RaiderIO\HeatmapHotspotServiceInterface;
use App\Service\Season\SeasonServiceInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\Fixtures\Traits\CreatesDungeon;
use Tests\TestCases\PublicTestCase;

#[Group('RaiderIO')]
#[Group('RaiderIOHeatmapGridResponse')]
final class RaiderIOHeatmapGridResponseTest extends PublicTestCase
{
    use CreatesDungeon;

    private const string URL = 'https://raider.io/api/live-tracking/heatmaps/grid?test=1';

    #[Test]
    public function toArray_givenInternalUserPassedWhileOtherUserAuthenticated_includesUrl(): void
    {
        // Arrange
        $authenticatedUser = User::factory()->create();

        try {
            $this->be($authenticatedUser);
            $response = $this->makeResponse(User::findOrFail(1));

            // Act
            $array = $response->toArray();

            // Assert
            $this->assertArrayHasKey('url', $array);
            $this->assertSame(self::URL, $array['url']);
        } finally {
            $authenticatedUser->delete();
        }
    }

    #[Test]
    public function toArray_givenNoUserPassedWhileInternalUserAuthenticated_omitsUrl(): void
    {
        // Arrange
        $this->be(User::findOrFail(1));
        $response = $this->makeResponse(null);

        // Act
        $array = $response->toArray();

        // Assert
        $this->assertArrayNotHasKey('url', $array);
        $this->assertSame(5, $array['weight_max']);
    }

    #[Test]
    public function toArray_givenNonInternalUserPassed_omitsUrl(): void
    {
        // Arrange
        $user = User::factory()->create();

        try {
            $response = $this->makeResponse($user);

            // Act
            $array = $response->toArray();

            // Assert
            $this->assertArrayNotHasKey('url', $array);
        } finally {
            $user->delete();
        }
    }

    #[Test]
    public function toArray_givenBetaTesterPassed_omitsUrl(): void
    {
        // Arrange
        $user = User::factory()->create();

        try {
            $user->addRole(Role::ROLE_BETA_TESTER);
            $response = $this->makeResponse($user);

            // Act
            $array = $response->toArray();

            // Assert
            $this->assertArrayNotHasKey('url', $array);
        } finally {
            $user->delete();
        }
    }

    #[Test]
    public function toArray_givenInternalTeamPassed_includesUrl(): void
    {
        // Arrange
        $user = User::factory()->create();

        try {
            $user->addRole(Role::ROLE_INTERNAL_TEAM);
            $response = $this->makeResponse($user);

            // Act
            $array = $response->toArray();

            // Assert
            $this->assertSame(self::URL, $array['url'] ?? null);
        } finally {
            $user->delete();
        }
    }

    #[Test]
    public function toArray_givenNoHotspots_omitsHotspots(): void
    {
        // Arrange
        /** @var MockObject&HeatmapHotspotServiceInterface $heatmapHotspotService */
        $heatmapHotspotService = $this->createMockPublic(HeatmapHotspotServiceInterface::class);
        $heatmapHotspotService->expects($this->never())->method('convertHotspotsByFloor');
        $response = $this->makeResponse(null)->setHotspots($heatmapHotspotService, null);

        // Act
        $array = $response->toArray();

        // Assert
        $this->assertArrayNotHasKey('hotspots', $array);
        $this->assertSame([], $array['data']);
        $this->assertSame(5, $array['weight_max']);
    }

    #[Test]
    public function toArray_givenHotspots_includesThemConvertedForTheDungeon(): void
    {
        // Arrange
        $hotspotsByFloor = ['1' => [['polygon' => [[0, 0], [10, 0], [0, 10]], 'npcs' => [['npcId' => 448, 'deaths' => 3]]]]];
        $converted       = [['floor_id' => 1, 'hotspots' => []]];
        $response        = $this->makeResponse(null);

        /** @var MockObject&HeatmapHotspotServiceInterface $heatmapHotspotService */
        $heatmapHotspotService = $this->createMockPublic(HeatmapHotspotServiceInterface::class);
        $heatmapHotspotService->expects($this->once())
            ->method('convertHotspotsByFloor')
            ->with($this->isInstanceOf(Dungeon::class), $this->anything(), $hotspotsByFloor, false, false)
            ->willReturn($converted);

        // Act
        $array = $response->setHotspots($heatmapHotspotService, $hotspotsByFloor)
            ->setUseFacade(false)
            ->setUseFacadeNavigation(false)
            ->toArray();

        // Assert
        $this->assertSame($converted, $array['hotspots']);
    }

    private function makeResponse(?User $user): RaiderIOHeatmapGridResponse
    {
        $dungeon = $this->createDungeon();
        $filter  = new CombatLogEventFilter(
            app(SeasonServiceInterface::class),
            $dungeon,
            CombatLogEventEventType::NpcDeath,
            CombatLogEventDataType::PlayerPosition,
        );

        return new RaiderIOHeatmapGridResponse(
            app(CoordinatesServiceInterface::class),
            $filter,
            [],
            1,
            5,
            self::URL,
            false,
            $user,
        );
    }
}
