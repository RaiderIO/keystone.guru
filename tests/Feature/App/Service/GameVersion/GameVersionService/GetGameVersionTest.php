<?php

namespace Tests\Feature\App\Service\GameVersion\GameVersionService;

use App\Models\GameVersion\GameVersion;
use App\Service\Cookies\CookieServiceInterface;
use App\Service\GameVersion\GameVersionService;
use App\Service\View\ViewServiceInterface;
use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
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
        $retail  = GameVersion::firstWhere('key', GameVersion::GAME_VERSION_RETAIL);
        $service = $this->buildService(collect());

        $_COOKIE['game_version'] = GameVersion::GAME_VERSION_RETAIL;

        try {
            // Act
            $result = $service->getGameVersion(null);

            // Assert
            $this->assertSame($retail->id, $result->id);
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
            // CI runs with the model cache on, which would answer these queries without reaching the database.
            app('model-cache')->runDisabled($callback);
        } finally {
            DB::disableQueryLog();
        }

        return collect(DB::getQueryLog())
            ->filter(static fn(array $query): bool => str_contains($query['query'], 'from `game_versions`'))
            ->count();
    }
}
