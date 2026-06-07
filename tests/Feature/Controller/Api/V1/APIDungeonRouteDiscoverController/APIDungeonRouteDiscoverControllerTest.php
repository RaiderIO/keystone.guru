<?php

namespace Tests\Feature\Controller\Api\V1\APIDungeonRouteDiscoverController;

use App\Models\Dungeon;
use App\Models\GameVersion\GameVersion;
use App\Service\DungeonRoute\DiscoverServiceInterface;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('API')]
#[Group('APIDungeonRouteDiscover')]
final class APIDungeonRouteDiscoverControllerTest extends PublicTestCase
{
    /**
     * @throws Exception
     */
    private function mockDiscoverService(): MockObject&DiscoverServiceInterface
    {
        /** @var MockObject&DiscoverServiceInterface $mock */
        $mock = $this->createMockPublic(DiscoverServiceInterface::class);
        $mock->method('withCache')->willReturnSelf();
        $mock->method('withLimit')->willReturnSelf();
        $mock->method('withGameVersion')->willReturnSelf();
        $mock->method('withBuilder')->willReturnSelf();
        $mock->method('withSeason')->willReturnSelf();
        $mock->method('withExpansion')->willReturnSelf();
        $mock->method('excludeTeam')->willReturnSelf();
        $mock->method('popular')->willReturn(new Collection());
        $mock->method('new')->willReturn(new Collection());
        $mock->method('popularByDungeon')->willReturn(new Collection());
        $mock->method('newByDungeon')->willReturn(new Collection());
        app()->instance(DiscoverServiceInterface::class, $mock);

        return $mock;
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function popular_givenValidGameVersion_shouldReturnOk(): void
    {
        // Arrange
        $gameVersion = GameVersion::firstOrFail();
        $this->mockDiscoverService();

        // Act
        $response = $this->getJson(route('api.v1.discover.popular', ['gameVersion' => $gameVersion->key]));

        // Assert
        $response->assertOk();
        $response->assertJsonStructure(['data']);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function popular_givenOffsetAndCount_shouldPassToService(): void
    {
        // Arrange
        $gameVersion = GameVersion::firstOrFail();
        $mock        = $this->mockDiscoverService();
        $mock->expects($this->once())->method('withLimit')->with(5)->willReturnSelf();

        // Act
        $response = $this->getJson(route('api.v1.discover.popular', [
            'gameVersion' => $gameVersion->key,
            'offset'      => 20,
            'count'       => 5,
        ]));

        // Assert
        $response->assertOk();
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function popular_givenCountAboveMax_shouldReturn422(): void
    {
        // Arrange
        $gameVersion = GameVersion::firstOrFail();
        $this->mockDiscoverService();

        // Act
        $response = $this->getJson(route('api.v1.discover.popular', [
            'gameVersion' => $gameVersion->key,
            'count'       => 101,
        ]));

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonPath('data.count', fn($v) => !empty($v));
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function popular_givenOffsetBelowZero_shouldReturn422(): void
    {
        // Arrange
        $gameVersion = GameVersion::firstOrFail();
        $this->mockDiscoverService();

        // Act
        $response = $this->getJson(route('api.v1.discover.popular', [
            'gameVersion' => $gameVersion->key,
            'offset'      => -1,
        ]));

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonPath('data.offset', fn($v) => !empty($v));
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function popular_givenInvalidGameVersion_shouldReturn404(): void
    {
        // Arrange
        $this->mockDiscoverService();

        // Act
        $response = $this->getJson(route('api.v1.discover.popular', ['gameVersion' => 'nonexistent-game-version']));

        // Assert
        $response->assertNotFound();
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function new_givenValidGameVersion_shouldReturnOk(): void
    {
        // Arrange
        $gameVersion = GameVersion::firstOrFail();
        $this->mockDiscoverService();

        // Act
        $response = $this->getJson(route('api.v1.discover.new', ['gameVersion' => $gameVersion->key]));

        // Assert
        $response->assertOk();
        $response->assertJsonStructure(['data']);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function new_givenCountAboveMax_shouldReturn422(): void
    {
        // Arrange
        $gameVersion = GameVersion::firstOrFail();
        $this->mockDiscoverService();

        // Act
        $response = $this->getJson(route('api.v1.discover.new', [
            'gameVersion' => $gameVersion->key,
            'count'       => 101,
        ]));

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonPath('data.count', fn($v) => !empty($v));
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function dungeonPopular_givenValidParams_shouldReturnOk(): void
    {
        // Arrange
        $gameVersion = GameVersion::firstOrFail();
        $dungeon     = Dungeon::firstOrFail();
        $this->mockDiscoverService();

        // Act
        $response = $this->getJson(route('api.v1.discover.dungeon.popular', [
            'gameVersion' => $gameVersion->key,
            'dungeon'     => $dungeon->slug,
        ]));

        // Assert
        $response->assertOk();
        $response->assertJsonStructure(['data']);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function dungeonNew_givenValidParams_shouldReturnOk(): void
    {
        // Arrange
        $gameVersion = GameVersion::firstOrFail();
        $dungeon     = Dungeon::firstOrFail();
        $this->mockDiscoverService();

        // Act
        $response = $this->getJson(route('api.v1.discover.dungeon.new', [
            'gameVersion' => $gameVersion->key,
            'dungeon'     => $dungeon->slug,
        ]));

        // Assert
        $response->assertOk();
        $response->assertJsonStructure(['data']);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function dungeonPopular_givenInvalidDungeon_shouldReturn404(): void
    {
        // Arrange
        $gameVersion = GameVersion::firstOrFail();
        $this->mockDiscoverService();

        // Act
        $response = $this->getJson(route('api.v1.discover.dungeon.popular', [
            'gameVersion' => $gameVersion->key,
            'dungeon'     => 'nonexistent-dungeon-slug',
        ]));

        // Assert
        $response->assertNotFound();
    }
}
