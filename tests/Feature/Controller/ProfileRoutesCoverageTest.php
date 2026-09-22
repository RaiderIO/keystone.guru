<?php

namespace Tests\Feature\Controller;

use App\Models\GameVersion\GameVersion;
use App\Models\Laratrust\Role;
use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
final class ProfileRoutesCoverageTest extends PublicTestCase
{
    #[Test]
    public function routes_givenRetailUser_linksCoverageSearchButtonsToTheDungeonRouteSearch(): void
    {
        // Arrange
        $user = User::factory()->create();
        $user->addRole(Role::ROLE_USER);

        try {
            // Act
            $response = $this->actingAs($user)->get(route('profile.routes'));

            // Assert
            $response->assertOk();
            $html = $response->getContent();

            $this->assertStringContainsString(
                sprintf('%s/', route('dungeon.dungeonroute.search.gameversion', ['gameVersion' => GameVersion::GAME_VERSION_RETAIL])),
                $html,
            );
            $this->assertStringNotContainsString(sprintf('%s?', route('dungeonroutes.search')), $html);
        } finally {
            $user->delete();
        }
    }
}
