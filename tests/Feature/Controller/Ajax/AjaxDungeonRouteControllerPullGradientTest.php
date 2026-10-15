<?php

namespace Tests\Feature\Controller\Ajax;

use App\Models\DungeonRoute\DungeonRoute;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\AjaxPublicTestCase;

#[Group('Controller')]
#[Group('DungeonRoute')]
final class AjaxDungeonRouteControllerPullGradientTest extends AjaxPublicTestCase
{
    #[Test]
    public function storePullGradient_givenAnEmptyGradient_clearsTheGradient(): void
    {
        // Arrange - removing every gradient handle submits an empty gradient, which arrives as null
        $dungeonRoute = DungeonRoute::factory()->create([
            'author_id'                  => 1,
            'expires_at'                 => null,
            'pull_gradient'              => '0 #ff0000,100 #00ff00',
            'pull_gradient_apply_always' => true,
        ]);

        try {
            // Act
            $response = $this->patch(sprintf('/ajax/%s/pullgradient', $dungeonRoute->public_key), [
                'pull_gradient'              => '',
                'pull_gradient_apply_always' => '',
            ]);

            // Assert
            $response->assertNoContent();
            $dungeonRoute->refresh();
            $this->assertSame('', $dungeonRoute->pull_gradient);
            $this->assertFalse((bool)$dungeonRoute->pull_gradient_apply_always);
        } finally {
            $dungeonRoute->delete();
        }
    }
}
