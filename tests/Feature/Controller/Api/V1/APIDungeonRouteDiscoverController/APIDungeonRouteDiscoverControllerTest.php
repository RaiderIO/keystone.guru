<?php

namespace Tests\Feature\Controller\Api\V1\APIDungeonRouteDiscoverController;

use App\Models\Dungeon;
use App\Models\GameVersion\GameVersion;
use App\Service\DungeonRoute\DiscoverServiceInterface;
use App\Service\DungeonRoute\ThumbnailServiceInterface;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\DataProvider;
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
    public function newest_givenValidGameVersion_shouldReturnOk(): void
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
    public function newest_givenCountAboveMax_shouldReturn422(): void
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

    /**
     * @throws Exception
     */
    #[Test]
    #[DataProvider('endpoint_givenRoutes_provider')]
    public function endpoint_givenRoutes_reportsThemAsDisplayed(string $routeName, string $discoverMethod, bool $withDungeon): void
    {
        // Arrange
        $gameVersion   = GameVersion::firstOrFail();
        $dungeon       = Dungeon::firstOrFail();
        $dungeonRoutes = new Collection();

        $discoverService = $this->createMockPublic(DiscoverServiceInterface::class);
        $discoverService->method('withCache')->willReturnSelf();
        $discoverService->method('withLimit')->willReturnSelf();
        $discoverService->method('withGameVersion')->willReturnSelf();
        $discoverService->method('withBuilder')->willReturnSelf();
        $discoverService->method($discoverMethod)->willReturn($dungeonRoutes);
        app()->instance(DiscoverServiceInterface::class, $discoverService);

        $thumbnailService = $this->createMockPublic(ThumbnailServiceInterface::class);
        $thumbnailService->expects($this->once())
            ->method('dungeonRoutesDisplayed')
            ->with($this->identicalTo($dungeonRoutes));
        app()->instance(ThumbnailServiceInterface::class, $thumbnailService);

        // Act
        $response = $this->getJson(route($routeName, array_filter([
            'gameVersion' => $gameVersion->key,
            'dungeon'     => $withDungeon ? $dungeon->slug : null,
        ])));

        // Assert
        $response->assertOk();
    }

    /**
     * @return array<string, array{string, string, bool}>
     */
    public static function endpoint_givenRoutes_provider(): array
    {
        return [
            'popular'         => ['api.v1.discover.popular', 'popular', false],
            'new'             => ['api.v1.discover.new', 'new', false],
            'dungeon popular' => ['api.v1.discover.dungeon.popular', 'popularByDungeon', true],
            'dungeon new'     => ['api.v1.discover.dungeon.new', 'newByDungeon', true],
        ];
    }
}
