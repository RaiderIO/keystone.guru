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
    public function breadcrumbGenerate_givenProfileViewKey_returnsMyPublicProfileLabel(): void
    {
        // Arrange
        $user = User::factory()->create();

        try {
            // Act
            $breadcrumbs = Breadcrumbs::generate('profile.view', $user);

            // Assert
            $this->assertNotEmpty($breadcrumbs);
            $this->assertSame(__('breadcrumbs.home.my_profile'), $breadcrumbs->last()->title);
        } finally {
            $user->delete();
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
