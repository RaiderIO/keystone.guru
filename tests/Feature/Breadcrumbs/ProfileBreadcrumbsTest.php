<?php

namespace Tests\Feature\Breadcrumbs;

use App\Models\User;
use Diglactic\Breadcrumbs\Breadcrumbs;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ProfileBreadcrumbsTest extends TestCase
{
    #[Test]
    public function breadcrumbGenerate_givenProfileEditKey_returnsAccountSettingsLabel(): void
    {
        // Arrange: no additional setup required, the breadcrumb takes no parameters

        // Act
        $breadcrumbs = Breadcrumbs::generate('profile.edit');

        // Assert
        $this->assertNotEmpty($breadcrumbs);
        $this->assertSame(__('breadcrumbs.home.account_settings'), $breadcrumbs->last()->title);
    }

    #[Test]
    public function breadcrumbGenerate_givenProfileViewKeyForAnyUser_returnsThatUsersNameInLabel(): void
    {
        // Arrange: two distinct users, since the label must reflect whichever profile is being
        // viewed, not just the currently authenticated user
        $viewedUser = User::factory()->create(['name' => 'SomeOtherUser']);
        $authUser   = User::factory()->create(['name' => 'AuthenticatedUser']);

        try {
            // Act
            $this->actingAs($authUser);
            $breadcrumbs = Breadcrumbs::generate('profile.view', $viewedUser);

            // Assert
            $this->assertNotEmpty($breadcrumbs);
            $this->assertSame(sprintf(__('view_profile.view.header'), $viewedUser->name), $breadcrumbs->last()->title);
        } finally {
            $viewedUser->delete();
            $authUser->delete();
        }
    }

    #[Test]
    public function breadcrumbGenerate_givenProfileFavoritesKey_returnsMyFavoritesLabel(): void
    {
        // Arrange: no additional setup required, the breadcrumb takes no parameters

        // Act
        $breadcrumbs = Breadcrumbs::generate('profile.favorites');

        // Assert
        $this->assertNotEmpty($breadcrumbs);
        $this->assertSame(__('breadcrumbs.home.my_favorites'), $breadcrumbs->last()->title);
    }
}
