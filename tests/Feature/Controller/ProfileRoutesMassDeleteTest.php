<?php

namespace Tests\Feature\Controller;

use App\Models\Laratrust\Role;
use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
final class ProfileRoutesMassDeleteTest extends PublicTestCase
{
    #[Test]
    public function routes_givenAUser_rendersTheDeleteButtonInTheRouteOverviewHeader(): void
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
            $user->delete();
        }
    }

    #[Test]
    public function routes_givenAUser_wiresTheDeleteButtonToTheDeletePicker(): void
    {
        // Arrange
        $user = User::factory()->create();
        $user->addRole(Role::ROLE_USER);

        try {
            // Act
            $response = $this->actingAs($user)->get(route('profile.routes'));

            // Assert
            $response->assertOk();
            $response->assertSee('id="routes_table_mass_delete_picker"', false);
            $response->assertSee('"openButtonSelector":"#routes_table_mass_delete"', false);
            $response->assertSee('"massDeletePickerSelector":"#routes_table_mass_delete_picker"', false);
        } finally {
            $user->delete();
        }
    }

    #[Test]
    public function favorites_givenAUser_leavesTheDeletePickerOut(): void
    {
        // Arrange
        $user = User::factory()->create();
        $user->addRole(Role::ROLE_USER);

        try {
            // Act
            $response = $this->actingAs($user)->get(route('profile.favorites'));

            // Assert
            $response->assertOk();
            $response->assertDontSee('routes_table_mass_delete', false);
            $response->assertSee('"massDeletePickerSelector":null', false);
        } finally {
            $user->delete();
        }
    }
}
