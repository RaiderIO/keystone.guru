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
        $classicEra = GameVersion::firstWhere('key', GameVersion::GAME_VERSION_CLASSIC_ERA);
        $this->actingAsGuest();

        $_COOKIE['game_version'] = GameVersion::GAME_VERSION_CLASSIC_ERA;

        try {
            // Act
            $result = GameVersion::getUserOrDefaultGameVersion();

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
        $this->actingAsGuest();
        unset($_COOKIE['game_version']);

        // Act
        $result = GameVersion::getUserOrDefaultGameVersion();

        // Assert
        $this->assertSame(GameVersion::getDefaultGameVersion()->id, $result->id);
    }

    #[Test]
    public function getUserOrDefaultGameVersion_givenGuestWithUnknownGameVersionCookie_returnsTheDefaultGameVersion(): void
    {
        // Arrange
        $this->actingAsGuest();

        $_COOKIE['game_version'] = 'not-a-game-version';

        try {
            // Act
            $result = GameVersion::getUserOrDefaultGameVersion();

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
        $this->actingAs($user);

        $_COOKIE['game_version'] = GameVersion::GAME_VERSION_RETAIL;

        try {
            // Act
            $result = GameVersion::getUserOrDefaultGameVersion();

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
        $this->actingAs($user);

        $_COOKIE['game_version'] = GameVersion::GAME_VERSION_CLASSIC_ERA;

        try {
            // Act
            $result = GameVersion::getUserOrDefaultGameVersion();

            // Assert
            $this->assertSame(GameVersion::getDefaultGameVersion()->id, $result->id);
        } finally {
            unset($_COOKIE['game_version']);
            $user->delete();
        }
    }
}
