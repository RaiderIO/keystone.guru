<?php

namespace Tests\Feature\Controller\AdminTools;

use App\Models\CombatLog\CombatLogParseFailure;
use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('AdminTools')]
final class AdminToolsCombatLogParseFailureControllerTest extends PublicTestCase
{
    private const int ADMIN_USER_ID     = 1;
    private const int NON_ADMIN_USER_ID = 3;

    /** @var array<int> */
    private array $createdFailureIds = [];

    protected function tearDown(): void
    {
        try {
            CombatLogParseFailure::query()->whereIn('id', $this->createdFailureIds)->delete();
        } finally {
            parent::tearDown();
        }
    }

    #[Test]
    public function index_givenAdmin_returnsOk(): void
    {
        // Arrange
        $this->be(User::findOrFail(self::ADMIN_USER_ID));

        // Act
        $response = $this->get(route('admin.tools.combatlog.parsefailures.view'));

        // Assert
        $response->assertOk();
    }

    #[Test]
    public function index_givenNonAdmin_returnsForbidden(): void
    {
        // Arrange
        $this->be(User::findOrFail(self::NON_ADMIN_USER_ID));

        // Act
        $response = $this->get(route('admin.tools.combatlog.parsefailures.view'));

        // Assert
        $response->assertForbidden();
    }

    #[Test]
    public function resolve_givenOpenFailure_marksItResolved(): void
    {
        // Arrange
        $this->be(User::findOrFail(self::ADMIN_USER_ID));

        $failure                   = CombatLogParseFailure::factory()->create();
        $this->createdFailureIds[] = $failure->id;

        // Act
        $response = $this->post(route('admin.tools.combatlog.parsefailures.resolve', ['parseFailure' => $failure->id]));

        // Assert
        $response->assertRedirect(route('admin.tools.combatlog.parsefailures.view'));
        $this->assertNotNull($failure->fresh()->resolved_at);
    }
}
