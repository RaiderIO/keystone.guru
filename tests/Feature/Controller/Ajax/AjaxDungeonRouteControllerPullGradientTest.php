<?php

namespace Tests\Feature\Controller\Ajax;

use App\Models\DungeonRoute\DungeonRoute;
use PHPUnit\Framework\Attributes\DataProvider;
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

    #[Test]
    public function storePullGradient_givenAGradientFromTheEditor_storesIt(): void
    {
        // Arrange
        $pullGradient = '0 #ff0000,47.0588235 #00FF00,100 #0000ffcc';
        $dungeonRoute = DungeonRoute::factory()->create([
            'author_id'  => 1,
            'expires_at' => null,
        ]);

        try {
            // Act
            $response = $this->patch(sprintf('/ajax/%s/pullgradient', $dungeonRoute->public_key), [
                'pull_gradient'              => $pullGradient,
                'pull_gradient_apply_always' => '1',
            ]);

            // Assert
            $response->assertNoContent();
            $dungeonRoute->refresh();
            $this->assertSame($pullGradient, $dungeonRoute->pull_gradient);
            $this->assertTrue((bool)$dungeonRoute->pull_gradient_apply_always);
        } finally {
            $dungeonRoute->delete();
        }
    }

    #[Test]
    #[DataProvider('storePullGradient_givenAnInvalidGradient_Provider')]
    public function storePullGradient_givenAnInvalidGradient_returnsValidationErrorAndKeepsTheGradient(string $pullGradient): void
    {
        // Arrange
        $existingPullGradient = '0 #ff0000,100 #00ff00';
        $dungeonRoute         = DungeonRoute::factory()->create([
            'author_id'     => 1,
            'expires_at'    => null,
            'pull_gradient' => $existingPullGradient,
        ]);

        try {
            // Act
            $response = $this->patchJson(sprintf('/ajax/%s/pullgradient', $dungeonRoute->public_key), [
                'pull_gradient'              => $pullGradient,
                'pull_gradient_apply_always' => '0',
            ]);

            // Assert
            $response->assertUnprocessable();
            $response->assertJsonValidationErrors(['pull_gradient']);
            $this->assertSame($existingPullGradient, $dungeonRoute->refresh()->pull_gradient);
        } finally {
            $dungeonRoute->delete();
        }
    }

    /**
     * @return array<string, array{string}>
     */
    public static function storePullGradient_givenAnInvalidGradient_Provider(): array
    {
        return [
            'free text'             => ['not a gradient'],
            'named colour'          => ['0 red,100 blue'],
            'colour without hash'   => ['0 ff0000,100 00ff00'],
            'trailing markup'       => ['0 #ff0000,100 #00ff00"><b>'],
            'trailing comma'        => ['0 #ff0000,'],
            'longer than the limit' => [implode(',', array_fill(0, 200, '100 #ff0000'))],
        ];
    }

    #[Test]
    public function storePullGradient_givenAnInvalidApplyAlways_returnsValidationError(): void
    {
        // Arrange
        $dungeonRoute = DungeonRoute::factory()->create([
            'author_id'  => 1,
            'expires_at' => null,
        ]);

        try {
            // Act
            $response = $this->patchJson(sprintf('/ajax/%s/pullgradient', $dungeonRoute->public_key), [
                'pull_gradient'              => '0 #ff0000',
                'pull_gradient_apply_always' => 'sometimes',
            ]);

            // Assert
            $response->assertUnprocessable();
            $response->assertJsonValidationErrors(['pull_gradient_apply_always']);
        } finally {
            $dungeonRoute->delete();
        }
    }
}
