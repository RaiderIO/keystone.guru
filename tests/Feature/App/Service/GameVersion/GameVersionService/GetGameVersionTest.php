<?php

namespace Tests\Feature\App\Service\GameVersion\GameVersionService;

use App\Models\GameVersion\GameVersion;
use App\Models\User;
use App\Service\Cookies\CookieServiceInterface;
use App\Service\GameVersion\GameVersionService;
use App\Service\View\ViewServiceInterface;
use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('GameVersionService')]
#[Group('GetGameVersion')]
final class GetGameVersionTest extends PublicTestCase
{
    #[Test]
    public function getGameVersion_givenGuestCookieForAnActiveGameVersion_readsNoGameVersions(): void
    {
        // Arrange
        $retail  = GameVersion::firstWhere('key', GameVersion::GAME_VERSION_RETAIL);
        $service = $this->buildService(collect([$retail]));

        $_COOKIE['game_version'] = GameVersion::GAME_VERSION_RETAIL;

        try {
            // Act
            $result             = null;
            $gameVersionQueries = $this->countGameVersionQueries(function () use (&$result, $service): void {
                $result = $service->getGameVersion(null);
            });

            // Assert
            $this->assertSame(0, $gameVersionQueries);
            $this->assertSame($retail->id, $result->id);
        } finally {
            unset($_COOKIE['game_version']);
        }
    }

    #[Test]
    public function getGameVersion_givenGuestCookieForAGameVersionOutsideTheActiveList_readsItFromTheDatabase(): void
    {
        // Arrange
        // Not the default game version, so a cookie that is ignored cannot pass
        $classicEra = GameVersion::firstWhere('key', GameVersion::GAME_VERSION_CLASSIC_ERA);
        $this->assertNotSame(GameVersion::getDefaultGameVersion()->id, $classicEra->id);
        $service = $this->buildService(collect());

        $_COOKIE['game_version'] = GameVersion::GAME_VERSION_CLASSIC_ERA;

        try {
            // Act
            $result = $service->getGameVersion(null);

            // Assert
            $this->assertSame($classicEra->id, $result->id);
        } finally {
            unset($_COOKIE['game_version']);
        }
    }

    #[Test]
    public function getGameVersion_givenGuestCookieForAnUnknownKey_returnsTheDefaultGameVersion(): void
    {
        // Arrange
        $service = $this->buildService(collect());

        $_COOKIE['game_version'] = 'not-a-game-version';

        try {
            // Act
            $result = $service->getGameVersion(null);

            // Assert
            $this->assertSame(GameVersion::getDefaultGameVersion()->id, $result->id);
        } finally {
            unset($_COOKIE['game_version']);
        }
    }

    #[Test]
    #[DataProvider('retiredGameVersionKeyProvider')]
    public function getGameVersion_givenGuestCookieForARetiredGameVersion_returnsTheDefaultGameVersion(
        string $retiredGameVersionKey,
    ): void {
        // Arrange
        $service = $this->buildService(collect());

        $_COOKIE['game_version'] = $retiredGameVersionKey;

        try {
            // Act
            $result = $service->getGameVersion(null);

            // Assert
            $this->assertSame(GameVersion::getDefaultGameVersion()->id, $result->id);
        } finally {
            unset($_COOKIE['game_version']);
        }
    }

    #[Test]
    #[DataProvider('retiredGameVersionKeyProvider')]
    public function getGameVersion_givenGuestCookieForARetiredGameVersionInTheCachedList_returnsTheDefaultGameVersion(
        string $retiredGameVersionKey,
    ): void {
        // Arrange
        $retiredGameVersion = GameVersion::query()->where('key', $retiredGameVersionKey)->firstOrFail();
        $service            = $this->buildService(collect([$retiredGameVersion]));

        $_COOKIE['game_version'] = $retiredGameVersionKey;

        try {
            // Act
            $result = $service->getGameVersion(null);

            // Assert
            $this->assertSame(GameVersion::getDefaultGameVersion()->id, $result->id);
        } finally {
            unset($_COOKIE['game_version']);
        }
    }

    #[Test]
    #[DataProvider('retiredGameVersionKeyProvider')]
    public function getGameVersion_givenUserWithARetiredGameVersion_returnsAndSavesTheDefaultGameVersion(
        string $retiredGameVersionKey,
    ): void {
        // Arrange
        $service = $this->buildService(collect());
        $user    = User::factory()->create([
            'game_version_id' => GameVersion::ALL[$retiredGameVersionKey],
        ]);

        try {
            // Act
            $result = $service->getGameVersion($user);

            // Assert
            $defaultGameVersionId = GameVersion::getDefaultGameVersion()->id;
            $this->assertSame($defaultGameVersionId, $result->id);
            $this->assertSame($defaultGameVersionId, $user->refresh()->game_version_id);
        } finally {
            $user->delete();
        }
    }

    /**
     * @return array<string, array{string}>
     */
    public static function retiredGameVersionKeyProvider(): array
    {
        return [
            'wrath'        => [GameVersion::GAME_VERSION_WRATH],
            'cata'         => [GameVersion::GAME_VERSION_CATA],
            'legion remix' => [GameVersion::GAME_VERSION_LEGION_REMIX],
        ];
    }

    #[Test]
    public function getGameVersion_givenUserWithAnActiveGameVersionAndAGameVersionCookie_returnsTheUsersGameVersion(): void
    {
        // Arrange
        $classicEra = GameVersion::firstWhere('key', GameVersion::GAME_VERSION_CLASSIC_ERA);
        $retail     = GameVersion::firstWhere('key', GameVersion::GAME_VERSION_RETAIL);
        $this->assertNotSame(GameVersion::getDefaultGameVersion()->id, $classicEra->id);
        $service = $this->buildService(collect([$classicEra, $retail]));
        $user    = User::factory()->create(['game_version_id' => $classicEra->id]);

        $_COOKIE['game_version'] = GameVersion::GAME_VERSION_RETAIL;

        try {
            // Act
            $result = $service->getGameVersion($user);

            // Assert
            $this->assertSame($classicEra->id, $result->id);
            $this->assertSame($classicEra->id, $user->refresh()->game_version_id);
        } finally {
            unset($_COOKIE['game_version']);
            $user->delete();
        }
    }

    /**
     * @param Collection<int, GameVersion> $activeGameVersions
     */
    private function buildService(Collection $activeGameVersions): GameVersionService
    {
        $viewService = $this->createMockPublic(ViewServiceInterface::class);
        $viewService->method('getAllGameVersions')->willReturn($activeGameVersions);

        return new GameVersionService($this->createMockPublic(CookieServiceInterface::class), $viewService);
    }

    private function countGameVersionQueries(Closure $callback): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        try {
            $callback();
        } finally {
            DB::disableQueryLog();
        }

        return collect(DB::getQueryLog())
            ->filter(static fn(array $query): bool => str_contains($query['query'], 'from `game_versions`'))
            ->count();
    }
}
