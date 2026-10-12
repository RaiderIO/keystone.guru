<?php

namespace Tests\Feature\Http;

use App\Models\GameVersion\GameVersion;
use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Http')]
#[Group('Compendium')]
final class EnsureGameVersionHasCompendiumTest extends PublicTestCase
{
    #[Test]
    public function handle_givenUserOnRetail_showsTheCompendium(): void
    {
        // Arrange
        $user = User::factory()->create(['game_version_id' => GameVersion::ALL[GameVersion::GAME_VERSION_RETAIL]]);

        try {
            // Act
            $response = $this->actingAs($user)->get(route('compendium.index'));

            // Assert
            $response->assertOk();
        } finally {
            $user->delete();
        }
    }

    #[Test]
    public function handle_givenUserOnClassicEra_redirectsHomeWithWarning(): void
    {
        // Arrange
        $user = User::factory()->create(['game_version_id' => GameVersion::ALL[GameVersion::GAME_VERSION_CLASSIC_ERA]]);

        try {
            // Act
            $response = $this->actingAs($user)->get(route('npc.compendium.index'));

            // Assert
            $response->assertRedirect(route('home'));
            $response->assertSessionHas('warning', __('controller.compendium.flash.unavailable_for_game_version'));
        } finally {
            $user->delete();
        }
    }

    #[Test]
    public function handle_givenGuestWithClassicEraCookie_redirectsHome(): void
    {
        // Arrange
        $this->actingAsGuest();
        $_COOKIE['game_version'] = GameVersion::GAME_VERSION_CLASSIC_ERA;

        try {
            // Act
            $response = $this->get(route('compendium.class.index'));

            // Assert
            $response->assertRedirect(route('home'));
        } finally {
            unset($_COOKIE['game_version']);
        }
    }

    #[Test]
    public function handle_givenAjaxSearchOnRetail_reachesTheController(): void
    {
        // Arrange
        $user = User::factory()->create(['game_version_id' => GameVersion::ALL[GameVersion::GAME_VERSION_RETAIL]]);

        try {
            // Act
            $response = $this->actingAs($user)->call('GET', route('ajax.npc.compendium.search'), [], [], [], [
                'HTTP_X-Requested-With' => 'XMLHttpRequest',
            ]);

            // Assert - the request validation rejects the missing dungeon_id, so the middleware let it through
            $response->assertUnprocessable();
        } finally {
            $user->delete();
        }
    }

    #[Test]
    public function handle_givenAjaxSearchOnClassicEra_returnsNotFound(): void
    {
        // Arrange
        $user = User::factory()->create(['game_version_id' => GameVersion::ALL[GameVersion::GAME_VERSION_CLASSIC_ERA]]);

        try {
            // Act
            $response = $this->actingAs($user)->call('GET', route('ajax.npc.compendium.search'), [], [], [], [
                'HTTP_X-Requested-With' => 'XMLHttpRequest',
            ]);

            // Assert
            $response->assertNotFound();
        } finally {
            $user->delete();
        }
    }
}
