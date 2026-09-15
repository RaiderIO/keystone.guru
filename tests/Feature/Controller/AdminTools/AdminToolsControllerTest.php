<?php

namespace Tests\Feature\Controller\AdminTools;

use App\Jobs\DropCaches;
use App\Models\Laratrust\Role;
use App\Models\User;
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
        $response = $this->get(route('admin.tools.cache.drop'));

        // Assert
        $response->assertRedirect(route('admin.tools'));
        Queue::assertPushed(DropCaches::class);
    }
}
