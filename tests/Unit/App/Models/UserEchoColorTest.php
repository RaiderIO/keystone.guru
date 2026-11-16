<?php

namespace Tests\Unit\App\Models;

use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Models')]
#[Group('Echo')]
final class UserEchoColorTest extends PublicTestCase
{
    #[Test]
    public function getEchoColorForBroadcast_givenHexEchoColor_returnsThatColor(): void
    {
        // Arrange
        $user = User::factory()->make(['echo_color' => '#12ab9F']);

        // Act
        $result = $user->getEchoColorForBroadcast();

        // Assert
        $this->assertSame('#12ab9F', $result);
    }

    #[Test]
    public function getEchoColorForBroadcast_givenNonHexEchoColor_returnsHexColor(): void
    {
        // Arrange
        $user = User::factory()->make(['echo_color' => 'red;}</style><script>alert(1)</script>']);

        // Act
        $result = $user->getEchoColorForBroadcast();

        // Assert
        $this->assertMatchesRegularExpression('/^#[a-f0-9]{6}$/D', $result);
    }

    #[Test]
    public function getEchoColorForBroadcast_givenNullEchoColor_returnsHexColor(): void
    {
        // Arrange
        $user = User::factory()->make(['echo_color' => null]);

        // Act
        $result = $user->getEchoColorForBroadcast();

        // Assert
        $this->assertMatchesRegularExpression('/^#[a-f0-9]{6}$/D', $result);
    }

    #[Test]
    public function getEchoColorForBroadcast_givenNonHexEchoColor_returnsTheSameColorOnEveryCall(): void
    {
        // Arrange
        $user        = User::factory()->make(['echo_color' => 'not a color']);
        $sameKeyUser = User::factory()->make(['echo_color' => 'something else', 'public_key' => $user->public_key]);

        // Act
        $first  = $user->getEchoColorForBroadcast();
        $second = $sameKeyUser->getEchoColorForBroadcast();

        // Assert
        $this->assertSame($first, $second);
    }
}
