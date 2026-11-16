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
    #[DataProvider('storePullGradient_givenAGradientInAnotherNotation_Provider')]
    public function storePullGradient_givenAGradientInAnotherNotation_storesItNormalised(string $pullGradient, string $expectedPullGradient): void
    {
        // Arrange
        $dungeonRoute = DungeonRoute::factory()->create([
            'author_id'     => 1,
            'expires_at'    => null,
            'pull_gradient' => '0 #ff0000,100 #00ff00',
        ]);

        try {
            // Act
            $response = $this->patchJson(sprintf('/ajax/%s/pullgradient', $dungeonRoute->public_key), [
                'pull_gradient'              => $pullGradient,
                'pull_gradient_apply_always' => '0',
            ]);

            // Assert
            $response->assertNoContent();
            $this->assertSame($expectedPullGradient, $dungeonRoute->refresh()->pull_gradient);
        } finally {
            $dungeonRoute->delete();
        }
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function storePullGradient_givenAGradientInAnotherNotation_Provider(): array
    {
        return [
            'exponent float residue'      => ['2.220446049250313e-16 #FF0000,100 #00FF00', '0 #FF0000,100 #00FF00'],
            'small exponent'              => ['1e-3 #ff0000,100 #00ff00', '0.001 #ff0000,100 #00ff00'],
            'upper case exponent'         => ['0 #ff0000,5E1 #00ff00', '0 #ff0000,50 #00ff00'],
            'exponent with a percentage'  => ['1e-7% #ff0000,100% #00ff00', '0% #ff0000,100% #00ff00'],
            'exponent past the right end' => ['0 #ff0000,1e5 #00ff00', '0 #ff0000,100 #00ff00'],
            'negative residue'            => ['-1e-16 #ff0000,100 #00ff00', '0 #ff0000,100 #00ff00'],
            'negative position'           => ['-3 #ff0000,100 #00ff00', '0 #ff0000,100 #00ff00'],
            'leading dot'                 => ['.5 #ff0000,100 #00ff00', '0.5 #ff0000,100 #00ff00'],
            'spaces around handlers'      => ['0% #ff0000, 100% #00ff00 ', '0% #ff0000,100% #00ff00'],
        ];
    }

    #[Test]
    #[DataProvider('storePullGradient_givenTheStoredGradientWithUnreadableHandlers_Provider')]
    public function storePullGradient_givenTheStoredGradientWithUnreadableHandlers_storesTheReadableHandlers(
        string $storedPullGradient,
        string $expectedPullGradient,
    ): void {
        // Arrange - toggling "always apply" resends the gradient exactly as it was loaded
        $dungeonRoute = DungeonRoute::factory()->create([
            'author_id'     => 1,
            'expires_at'    => null,
            'pull_gradient' => $storedPullGradient,
        ]);

        try {
            // Act
            $response = $this->patchJson(sprintf('/ajax/%s/pullgradient', $dungeonRoute->public_key), [
                'pull_gradient'              => $storedPullGradient,
                'pull_gradient_apply_always' => '1',
            ]);

            // Assert
            $response->assertNoContent();
            $dungeonRoute->refresh();
            $this->assertSame($expectedPullGradient, $dungeonRoute->pull_gradient);
            $this->assertTrue((bool)$dungeonRoute->pull_gradient_apply_always);
        } finally {
            $dungeonRoute->delete();
        }
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function storePullGradient_givenTheStoredGradientWithUnreadableHandlers_Provider(): array
    {
        return [
            'trailing comma'         => ['0% #ff0000,', '0% #ff0000'],
            'handler without colour' => ['0% #ff0000, 50% notacolor, 100% #00ff00', '0% #ff0000,100% #00ff00'],
            'nothing readable'       => ['not a gradient', ''],
        ];
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
            'free text'                   => ['not a gradient'],
            'named colour'                => ['0 red,100 blue'],
            'colour without hash'         => ['0 ff0000,100 00ff00'],
            'trailing markup'             => ['0 #ff0000,100 #00ff00"><b>'],
            'trailing comma'              => ['0 #ff0000,'],
            'exponent and a named colour' => ['1e-7 red,100 #00ff00'],
            'exponent without a mantissa' => ['e5 #ff0000,100 #00ff00'],
            'exponent without a space'    => ['1e-7#ff0000,100 #00ff00'],
            'longer than the limit'       => [implode(',', array_fill(0, 200, '100 #ff0000'))],
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
