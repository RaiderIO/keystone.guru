<?php

namespace Tests\Unit\App\Models;

use App\Models\Npc\OpenWorldNpc;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Models')]
#[Group('OpenWorldNpc')]
final class OpenWorldNpcPortraitUrlTest extends PublicTestCase
{
    #[Test]
    public function getEnemyPortraitUrlAttribute_givenAPortrait_returnsItsOwnPortrait(): void
    {
        // Arrange
        $openWorldNpc = OpenWorldNpc::factory()->withPortrait()->make(['id' => 448]);

        // Act
        $url = $openWorldNpc->enemy_portrait_url;

        // Assert
        $this->assertSame('images/enemyportraits/448.png', $url);
    }

    #[Test]
    public function getEnemyPortraitUrlAttribute_givenNoPortrait_returnsTheUnknownPortrait(): void
    {
        // Arrange
        $openWorldNpc = OpenWorldNpc::factory()->make(['id' => 448]);

        // Act
        $url = $openWorldNpc->enemy_portrait_url;

        // Assert
        $this->assertSame('images/enemyportraits/unknown.png', $url);
    }
}
