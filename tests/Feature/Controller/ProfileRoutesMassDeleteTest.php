<?php

namespace Tests\Feature\Controller;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Laratrust\Role;
use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
final class ProfileRoutesMassDeleteTest extends PublicTestCase
{
    #[Test]
    public function routes_givenAUserWithRoutes_rendersTheDeleteButtonInTheRouteOverviewHeader(): void
    {
        // Arrange
        $user         = $this->createUser();
        $dungeonRoute = DungeonRoute::factory()->create(['author_id' => $user->id, 'expires_at' => null]);

        try {
            // Act
            $response = $this->actingAs($user)->get(route('profile.routes'));

            // Assert
            $response->assertOk();
            $html = $response->getContent();

            $headingPosition = strpos($html, __('view_profile.overview.route_overview'));
            $buttonPosition  = strpos($html, 'id="routes_table_mass_delete"');
            $filtersPosition = strpos($html, 'routes_table_filter_container');

            $this->assertNotFalse($headingPosition);
            $this->assertNotFalse($buttonPosition);
            $this->assertNotFalse($filtersPosition);
            $this->assertGreaterThan($headingPosition, $buttonPosition);
            $this->assertLessThan($filtersPosition, $buttonPosition);
            $this->assertSame(1, substr_count($html, 'id="routes_table_mass_delete"'));
        } finally {
            $dungeonRoute->delete();
            $user->delete();
        }
    }

    #[Test]
    public function routes_givenAUserWithRoutes_wiresTheDeleteButtonToTheDeletePicker(): void
    {
        // Arrange
        $user         = $this->createUser();
        $dungeonRoute = DungeonRoute::factory()->create(['author_id' => $user->id, 'expires_at' => null]);

        try {
            // Act
            $response = $this->actingAs($user)->get(route('profile.routes'));

            // Assert
            $response->assertOk();
            $response->assertSee('id="routes_table_mass_delete_picker"', false);
            $response->assertSee('"openButtonSelector":"#routes_table_mass_delete"', false);
            $response->assertSee('"massDeletePickerSelector":"#routes_table_mass_delete_picker"', false);
        } finally {
            $dungeonRoute->delete();
            $user->delete();
        }
    }

    #[Test]
    public function routes_givenAUserWithoutRoutes_leavesTheDeleteButtonOut(): void
    {
        // Arrange
        $user = $this->createUser();

        try {
            // Act
            $response = $this->actingAs($user)->get(route('profile.routes'));

            // Assert
            $response->assertOk();
            $response->assertSee(__('view_profile.overview.route_overview'), false);
            $response->assertDontSee('routes_table_mass_delete', false);
            $response->assertSee('"massDeletePickerSelector":null', false);
        } finally {
            $user->delete();
        }
    }

    #[Test]
    public function routes_givenAUserWithOnlySandboxRoutes_leavesTheDeleteButtonOut(): void
    {
        // Arrange
        $user         = $this->createUser();
        $dungeonRoute = DungeonRoute::factory()->create(['author_id' => $user->id, 'expires_at' => now()->addHour()]);

        try {
            // Act
            $response = $this->actingAs($user)->get(route('profile.routes'));

            // Assert
            $response->assertOk();
            $response->assertDontSee('routes_table_mass_delete', false);
        } finally {
            $dungeonRoute->delete();
            $user->delete();
        }
    }

    #[Test]
    public function favorites_givenAUserWithRoutes_leavesTheDeletePickerOut(): void
    {
        // Arrange
        $user         = $this->createUser();
        $dungeonRoute = DungeonRoute::factory()->create(['author_id' => $user->id, 'expires_at' => null]);

        try {
            // Act
            $response = $this->actingAs($user)->get(route('profile.favorites'));

            // Assert
            $response->assertOk();
            $response->assertDontSee('routes_table_mass_delete', false);
            $response->assertSee('"massDeletePickerSelector":null', false);
        } finally {
            $dungeonRoute->delete();
            $user->delete();
        }
    }

    private function createUser(): User
    {
        $user = User::factory()->create();
        $user->addRole(Role::ROLE_USER);

        return $user;
    }
}
