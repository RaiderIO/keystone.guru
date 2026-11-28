<?php

namespace Tests\Feature\App\Model\Dungeon;

use App\Models\Dungeon;
use App\Models\User;
use App\Service\Dungeon\DungeonServiceInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Dungeon')]
#[Group('GetUserOrDefaultDungeon')]
final class GetUserOrDefaultDungeonTest extends PublicTestCase
{
    #[Test]
    public function getUserOrDefaultDungeon_givenUserWhileAnotherUserIsLoggedIn_resolvesThePassedUsersContext(): void
    {
        // Arrange
        $user            = new User(['name' => 'passed']);
        $loggedInUser    = new User(['name' => 'logged in']);
        $expectedDungeon = new Dungeon(['key' => 'expected']);
        $this->actingAs($loggedInUser);

        $dungeonService = $this->createMockPublic(DungeonServiceInterface::class);
        $dungeonService->expects($this->once())
            ->method('getDungeonContext')
            ->with($this->identicalTo($user))
            ->willReturn($expectedDungeon);
        $this->app->instance(DungeonServiceInterface::class, $dungeonService);

        // Act
        $result = Dungeon::getUserOrDefaultDungeon($user);

        // Assert
        $this->assertSame($expectedDungeon, $result);
    }

    #[Test]
    public function getUserOrDefaultDungeon_givenNullWhileUserIsLoggedIn_resolvesTheGuestContext(): void
    {
        // Arrange
        $loggedInUser    = new User(['name' => 'logged in']);
        $expectedDungeon = new Dungeon(['key' => 'expected']);
        $this->actingAs($loggedInUser);

        $dungeonService = $this->createMockPublic(DungeonServiceInterface::class);
        $dungeonService->expects($this->once())
            ->method('getDungeonContext')
            ->with($this->isNull())
            ->willReturn($expectedDungeon);
        $this->app->instance(DungeonServiceInterface::class, $dungeonService);

        // Act
        $result = Dungeon::getUserOrDefaultDungeon(null);

        // Assert
        $this->assertSame($expectedDungeon, $result);
    }
}
