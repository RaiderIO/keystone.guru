<?php

namespace Tests\Feature\Controller\AdminTools;

use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('AdminTools')]
final class AdminToolsEnemyForcesControllerTest extends PublicTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->be(User::findOrFail(1));
    }

    #[Test]
    public function enemyforcesrecalculate_givenAuthenticatedAdmin_returnsOk(): void
    {
        // Arrange

        // Act
        $response = $this->get(route('admin.tools.enemyforces.recalculate.view'));

        // Assert
        $response->assertOk();
    }

    #[Test]
    public function toolsList_givenAuthenticatedAdmin_linksRecalculateButNotImport(): void
    {
        // Arrange

        // Act
        $response = $this->get(route('admin.tools'));

        // Assert
        $response->assertOk();
        $response->assertSee(route('admin.tools.enemyforces.recalculate.view'), false);
        $response->assertDontSee('admin/tools/enemyforces/import', false);
    }

    #[Test]
    public function enemyforcesImport_givenAuthenticatedAdmin_isNoLongerRouted(): void
    {
        // Arrange

        // Act
        $getResponse  = $this->get('/admin/tools/enemyforces/import');
        $postResponse = $this->post('/admin/tools/enemyforces/import', ['import_string' => '{"Npcs": []}']);

        // Assert
        $this->assertFalse(app('router')->has('admin.tools.enemyforces.import.view'));
        $this->assertFalse(app('router')->has('admin.tools.enemyforces.import.submit'));
        $getResponse->assertNotFound();
        // The GET-only fallback route answers any unrouted POST with 405
        $postResponse->assertMethodNotAllowed();
    }
}
