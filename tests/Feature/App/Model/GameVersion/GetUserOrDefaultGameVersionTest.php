<?php

namespace Tests\Feature\App\Model\GameVersion;

use App\Models\GameVersion\GameVersion;
use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('GameVersion')]
#[Group('GetUserOrDefaultGameVersion')]
final class GetUserOrDefaultGameVersionTest extends PublicTestCase
{
    #[Test]
    public function getUserOrDefaultGameVersion_givenGuestWithGameVersionCookie_returnsTheCookieGameVersion(): void
    {
        // Arrange
        $classicEra              = GameVersion::firstWhere('key', GameVersion::GAME_VERSION_CLASSIC_ERA);
        $_COOKIE['game_version'] = GameVersion::GAME_VERSION_CLASSIC_ERA;

        try {
            // Act
            $result = GameVersion::getUserOrDefaultGameVersion(null);

            // Assert
            $this->assertSame($classicEra->id, $result->id);
        } finally {
            unset($_COOKIE['game_version']);
        }
    }

    #[Test]
    public function getUserOrDefaultGameVersion_givenGuestWithoutGameVersionCookie_returnsTheDefaultGameVersion(): void
    {
        // Arrange
        unset($_COOKIE['game_version']);

        // Act
        $result = GameVersion::getUserOrDefaultGameVersion(null);

        // Assert
        $this->assertSame(GameVersion::getDefaultGameVersion()->id, $result->id);
    }

    #[Test]
    public function getUserOrDefaultGameVersion_givenGuestWithUnknownGameVersionCookie_returnsTheDefaultGameVersion(): void
    {
        // Arrange
        $_COOKIE['game_version'] = 'not-a-game-version';

        try {
            // Act
            $result = GameVersion::getUserOrDefaultGameVersion(null);

            // Assert
            $this->assertSame(GameVersion::getDefaultGameVersion()->id, $result->id);
        } finally {
            unset($_COOKIE['game_version']);
        }
    }

    #[Test]
    public function getUserOrDefaultGameVersion_givenUserWithGameVersionAndDifferentCookie_returnsTheUserGameVersion(): void
    {
        // Arrange
        $classicEra = GameVersion::firstWhere('key', GameVersion::GAME_VERSION_CLASSIC_ERA);
        $user       = User::factory()->create(['game_version_id' => $classicEra->id]);

        $_COOKIE['game_version'] = GameVersion::GAME_VERSION_RETAIL;

        try {
            // Act
            $result = GameVersion::getUserOrDefaultGameVersion($user);

            // Assert
            $this->assertSame($classicEra->id, $result->id);
        } finally {
            unset($_COOKIE['game_version']);
            $user->delete();
        }
    }

    #[Test]
    public function getUserOrDefaultGameVersion_givenUserWithoutGameVersion_returnsTheDefaultGameVersion(): void
    {
        // Arrange
        $user = User::factory()->create(['game_version_id' => null]);

        $_COOKIE['game_version'] = GameVersion::GAME_VERSION_CLASSIC_ERA;

        try {
            // Act
            $result = GameVersion::getUserOrDefaultGameVersion($user);

            // Assert
            $this->assertSame(GameVersion::getDefaultGameVersion()->id, $result->id);
        } finally {
            unset($_COOKIE['game_version']);
            $user->delete();
        }
    }

    #[Test]
    public function getUserOrDefaultGameVersion_givenGuestWithRetiredGameVersionCookie_returnsTheDefaultGameVersion(): void
    {
        // Arrange
        $_COOKIE['game_version'] = GameVersion::GAME_VERSION_WRATH;

        try {
            // Act
            $result = GameVersion::getUserOrDefaultGameVersion(null);

            // Assert
            $this->assertNotSame(GameVersion::ALL[GameVersion::GAME_VERSION_WRATH], GameVersion::getDefaultGameVersion()->id);
            $this->assertSame(GameVersion::getDefaultGameVersion()->id, $result->id);
        } finally {
            unset($_COOKIE['game_version']);
        }
    }

    #[Test]
    public function getUserOrDefaultGameVersion_givenUserWithRetiredGameVersion_returnsTheDefaultGameVersion(): void
    {
        // Arrange
        $wrath = GameVersion::firstWhere('key', GameVersion::GAME_VERSION_WRATH);
        $this->assertTrue($wrath->isRetired(), 'Fixture assumption changed - Wrath must still be a retired game version.');
        $user = User::factory()->create(['game_version_id' => $wrath->id]);

        try {
            // Act
            $result = GameVersion::getUserOrDefaultGameVersion($user);

            // Assert
            $this->assertSame(GameVersion::getDefaultGameVersion()->id, $result->id);
        } finally {
            $user->delete();
        }
    }

    #[Test]
    public function getUserOrDefaultGameVersion_givenUserWhileAnotherUserIsLoggedIn_returnsThePassedUserGameVersion(): void
    {
        // Arrange
        $classicEra   = GameVersion::firstWhere('key', GameVersion::GAME_VERSION_CLASSIC_ERA);
        $retail       = GameVersion::firstWhere('key', GameVersion::GAME_VERSION_RETAIL);
        $user         = null;
        $loggedInUser = null;

        try {
            $user         = User::factory()->create(['game_version_id' => $classicEra->id]);
            $loggedInUser = User::factory()->create(['game_version_id' => $retail->id]);
            $this->actingAs($loggedInUser);

            // Act
            $result = GameVersion::getUserOrDefaultGameVersion($user);

            // Assert
            $this->assertSame($classicEra->id, $result->id);
        } finally {
            $user?->delete();
            $loggedInUser?->delete();
        }
    }

    #[Test]
    public function getUserOrDefaultGameVersion_givenNullWhileUserIsLoggedIn_returnsTheCookieGameVersion(): void
    {
        // Arrange
        $classicEra   = GameVersion::firstWhere('key', GameVersion::GAME_VERSION_CLASSIC_ERA);
        $retail       = GameVersion::firstWhere('key', GameVersion::GAME_VERSION_RETAIL);
        $loggedInUser = null;

        $_COOKIE['game_version'] = GameVersion::GAME_VERSION_CLASSIC_ERA;

        try {
            $loggedInUser = User::factory()->create(['game_version_id' => $retail->id]);
            $this->actingAs($loggedInUser);

            // Act
            $result = GameVersion::getUserOrDefaultGameVersion(null);

            // Assert
            $this->assertSame($classicEra->id, $result->id);
        } finally {
            unset($_COOKIE['game_version']);
            $loggedInUser?->delete();
        }
    }
}
