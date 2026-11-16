<?php

namespace Tests\Unit\App\Events;

use App\Events\UserColorChangedEvent;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Events')]
#[Group('Echo')]
final class UserColorChangedEventTest extends PublicTestCase
{
    #[Test]
    public function broadcastWith_givenNonHexEchoColor_returnsHexColors(): void
    {
        // Arrange
        $user  = User::factory()->make(['echo_color' => 'red;}</style><script>alert(1)</script>']);
        $event = new UserColorChangedEvent(DungeonRoute::factory()->make(), $user);

        // Act
        $result = $event->broadcastWith();

        // Assert
        $this->assertMatchesRegularExpression('/^#[a-f0-9]{6}$/D', $result['color']);
        $this->assertMatchesRegularExpression('/^#[a-f0-9]{6}$/D', $result['user']['color']);
        $this->assertSame($user->public_key, $result['user']['public_key']);
    }

    #[Test]
    public function broadcastWith_givenHexEchoColor_returnsThatColor(): void
    {
        // Arrange
        $user  = User::factory()->make(['echo_color' => '#12ab9F']);
        $event = new UserColorChangedEvent(DungeonRoute::factory()->make(), $user);

        // Act
        $result = $event->broadcastWith();

        // Assert
        $this->assertSame('#12ab9F', $result['color']);
        $this->assertSame('#12ab9F', $result['user']['color']);
    }
}
