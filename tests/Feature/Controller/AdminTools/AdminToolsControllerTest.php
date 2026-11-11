<?php

namespace Tests\Feature\Controller\AdminTools;

use App\Jobs\DropCaches;
use App\Models\Laratrust\Role;
use App\Models\User;
use App\Service\DungeonRoute\ThumbnailGenerationToggleServiceInterface;
use App\Service\ReadOnlyMode\ReadOnlyModeServiceInterface;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('AdminTools')]
final class AdminToolsControllerTest extends PublicTestCase
{
    private const int ADMIN_USER_ID = 1;

    #[Test]
    public function dropCache_givenAdmin_dispatchesDropCachesJobAndRedirectsWithoutRunningItInline(): void
    {
        // Arrange
        Queue::fake();
        $admin = User::findOrFail(self::ADMIN_USER_ID);
        $this->assertTrue($admin->hasRole(Role::ROLE_ADMIN), 'User id=1 must be admin (seed the DB).');

        // Act
        $this->be($admin);
        $response = $this->post(route('admin.tools.cache.drop'));

        // Assert
        $response->assertRedirect(route('admin.tools'));
        $response->assertSessionHas('status', __('controller.admintools.flash.caches_drop_queued'));
        Queue::assertPushed(DropCaches::class);
    }

    #[Test]
    public function dropCache_givenGetRequest_returnsMethodNotAllowedAndDispatchesNothing(): void
    {
        // Arrange
        Queue::fake();
        $admin = User::findOrFail(self::ADMIN_USER_ID);

        // Act
        $this->be($admin);
        $response = $this->get(route('admin.tools.cache.drop'));

        // Assert
        $response->assertMethodNotAllowed();
        Queue::assertNotPushed(DropCaches::class);
    }

    #[Test]
    public function toggleReadOnlyMode_givenPostRequest_flipsReadOnlyModeAndRedirects(): void
    {
        // Arrange
        $admin               = User::findOrFail(self::ADMIN_USER_ID);
        $readOnlyModeService = app()->make(ReadOnlyModeServiceInterface::class);
        $wasReadOnly         = $readOnlyModeService->isReadOnly();

        try {
            $readOnlyModeService->setReadOnly(false);
            $this->be($admin);

            // Act
            $response = $this->post(route('admin.tools.readonly.toggle'));

            // Assert
            $response->assertRedirect(route('admin.tools'));
            $response->assertSessionHas('status', __('controller.admintools.flash.read_only_mode_enabled'));
            $this->assertTrue($readOnlyModeService->isReadOnly());
        } finally {
            $readOnlyModeService->setReadOnly($wasReadOnly);
        }
    }

    #[Test]
    public function toggleReadOnlyMode_givenGetRequest_returnsMethodNotAllowedAndLeavesReadOnlyModeOff(): void
    {
        // Arrange
        $admin               = User::findOrFail(self::ADMIN_USER_ID);
        $readOnlyModeService = app()->make(ReadOnlyModeServiceInterface::class);
        $wasReadOnly         = $readOnlyModeService->isReadOnly();

        try {
            $readOnlyModeService->setReadOnly(false);
            $this->be($admin);

            // Act
            $response = $this->get(route('admin.tools.readonly.toggle'));

            // Assert
            $response->assertMethodNotAllowed();
            $this->assertFalse($readOnlyModeService->isReadOnly());
        } finally {
            $readOnlyModeService->setReadOnly($wasReadOnly);
        }
    }

    #[Test]
    public function exportDungeonData_givenPostRequest_runsTheMappingSaveCommand(): void
    {
        // Arrange
        Artisan::shouldReceive('call')->once()->with('mapping:save')->andReturn(0);
        $this->be(User::findOrFail(self::ADMIN_USER_ID));

        // Act
        $response = $this->post(route('admin.tools.datadump.exportdungeondata'));

        // Assert
        $response->assertOk();
        $response->assertViewIs('admin.tools.datadump.viewexporteddungeondata');
    }

    #[Test]
    public function exportDungeonData_givenGetRequest_returnsMethodNotAllowedAndRunsNothing(): void
    {
        // Arrange
        Artisan::shouldReceive('call')->never();
        $this->be(User::findOrFail(self::ADMIN_USER_ID));

        // Act
        $response = $this->get(route('admin.tools.datadump.exportdungeondata'));

        // Assert
        $response->assertMethodNotAllowed();
    }

    #[Test]
    public function toggleThumbnailGeneration_givenAdmin_flipsStateAndRedirects(): void
    {
        // Arrange
        $admin         = User::findOrFail(self::ADMIN_USER_ID);
        $toggleService = app()->make(ThumbnailGenerationToggleServiceInterface::class);

        try {
            $this->be($admin);

            // Act
            $pauseResponse = $this->post(route('admin.tools.thumbnails.toggle'));

            // Assert
            $pauseResponse->assertRedirect(route('admin.tools'));
            $this->assertTrue($toggleService->isPaused());

            // Act - toggling again resumes generation
            $resumeResponse = $this->post(route('admin.tools.thumbnails.toggle'));

            // Assert
            $resumeResponse->assertRedirect(route('admin.tools'));
            $this->assertFalse($toggleService->isPaused());
        } finally {
            $toggleService->setPaused(false);
        }
    }

    #[Test]
    public function toggleThumbnailGeneration_givenNonAdmin_isNotAllowed(): void
    {
        // Arrange
        $user          = User::factory()->create();
        $toggleService = app()->make(ThumbnailGenerationToggleServiceInterface::class);

        try {
            $user->addRole(Role::firstWhere('name', Role::ROLE_USER));
            $this->be($user);

            // Act
            $response = $this->post(route('admin.tools.thumbnails.toggle'));

            // Assert
            $response->assertForbidden();
            $this->assertFalse($toggleService->isPaused());
        } finally {
            $toggleService->setPaused(false);
            $user->delete();
        }
    }
}
