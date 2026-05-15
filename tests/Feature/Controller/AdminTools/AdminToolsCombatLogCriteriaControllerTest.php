<?php

namespace Tests\Feature\Controller\AdminTools;

use App\Models\CombatLog\CombatLogParsingCriterion;
use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('AdminTools')]
final class AdminToolsCombatLogCriteriaControllerTest extends PublicTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->be(User::findOrFail(1));
    }

    #[Test]
    public function criteria_givenAuthenticatedAdmin_returnsOk(): void
    {
        // Arrange

        // Act
        $response = $this->get(route('admin.tools.combatlog.criteria.view'));

        // Assert
        $response->assertOk();
    }

    #[Test]
    public function criteriareset_givenExistingCountsForToday_resetsCountsToZero(): void
    {
        // Arrange
        $criterion = CombatLogParsingCriterion::factory()->forDungeon(999901)->withCount(75)->create();

        try {
            // Act
            $response = $this->post(route('admin.tools.combatlog.criteria.reset'));

            // Assert
            $response->assertRedirect(route('admin.tools.combatlog.criteria.view'));
            $this->assertEquals(0, $criterion->fresh()->count);
        } finally {
            $criterion->delete();
        }
    }
}
