<?php

namespace Tests\Feature\Controller\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\PublishedState;
use App\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
class DungeonRouteLegacyControllerTest extends PublicTestCase
{
    /**
     * @return $this
     */
    protected function actingAsUser(): self
    {
        return $this->be(User::findOrFail(1));
    }

    #[Test]
    public function viewOld_givenNonExistingRoute_shouldReturn404(): void
    {
        // Arrange

        // Act
        $response = $this->get(route('dungeonroute.viewold', ['dungeonRoute' => 'abcdefg']));

        // Assert
        $response->assertNotFound();
    }

    #[Test]
    public function embedOld_givenNonExistingRoute_shouldReturn404(): void
    {
        // Arrange

        // Act
        $response = $this->get(route('dungeonroute.embedold', ['dungeonRoute' => 'abcdefg']));

        // Assert
        $response->assertNotFound();
    }

    #[Test]
    public function embedOldFloor_givenNonExistingRoute_shouldReturn404(): void
    {
        // Arrange

        // Act
        $response = $this->get(route('dungeonroute.embedold.floor', ['dungeonRoute' => 'abcdefg', 'floorIndex' => '1']));

        // Assert
        $response->assertNotFound();
    }

    #[Test]
    public function viewFloorOld_givenNonExistingRoute_shouldReturn404(): void
    {
        // Arrange

        // Act
        $response = $this->get(route('dungeonroute.viewold.floor', ['dungeonRoute' => 'abcdefg', 'floorIndex' => '1']));

        // Assert
        $response->assertNotFound();
    }

    #[Test]
    public function previewOld_givenNonExistingRoute_shouldReturn404(): void
    {
        // Arrange

        // Act
        $response = $this->get(route('dungeonroute.previewold', ['dungeonRoute' => 'abcdefg', 'floorIndex' => '1']));

        // Assert
        $response->assertNotFound();
    }

    #[Test]
    public function edit_givenNonExistingRoute_shouldReturn404(): void
    {
        // Arrange

        // Act
        $response = $this->get(route('dungeonroute.editold', ['dungeonRoute' => 'abcdefg']));

        // Assert
        $response->assertNotFound();
    }

    #[Test]
    public function editFloor_givenNonExistingRoute_shouldReturn404(): void
    {
        // Arrange

        // Act
        $response = $this->get(route('dungeonroute.editold.floor', ['dungeonRoute' => 'abcdefg', 'floorIndex' => '1']));

        // Assert
        $response->assertNotFound();
    }

    #[Test]
    public function cloneOld_givenNonExistingRoute_shouldReturn404(): void
    {
        // Arrange

        // Act
        $response = $this->actingAsUser()->get(route('dungeonroute.cloneold', ['dungeonRoute' => 'abcdefg']));

        // Assert
        $response->assertNotFound();
    }

    #[Test]
    public function claimOld_givenNonExistingRoute_shouldReturn404(): void
    {
        // Arrange

        // Act
        $response = $this->actingAsUser()->get(route('dungeonroute.claimold', ['dungeonRoute' => 'abcdefg']));

        // Assert
        $response->assertNotFound();
    }

    /**
     * @param array<string, string> $legacyParameters
     * @param array<string, string> $canonicalParameters
     */
    #[Test]
    #[DataProvider('legacyRouteProvider')]
    public function legacyRoute_givenExistingRoute_redirectsToTheCanonicalRoute(
        string $legacyRouteName,
        string $canonicalRouteName,
        array  $legacyParameters,
        array  $canonicalParameters,
    ): void {
        // Arrange
        $dungeonRoute = DungeonRoute::factory()->create([
            'author_id'          => 1,
            'expires_at'         => null,
            'published_state_id' => PublishedState::ALL[PublishedState::WORLD],
        ]);

        try {
            // Act
            $response = $this->actingAsUser()->get(route($legacyRouteName, ['dungeonRoute' => $dungeonRoute] + $legacyParameters));

            // Assert
            $response->assertRedirect(route($canonicalRouteName, [
                'dungeon'      => $dungeonRoute->dungeon,
                'dungeonroute' => $dungeonRoute,
                'title'        => $dungeonRoute->getTitleSlug(),
            ] + $canonicalParameters));
        } finally {
            $dungeonRoute->delete();
        }
    }

    /**
     * @return array<string, array{string, string, array<string, string>, array<string, string>}>
     */
    public static function legacyRouteProvider(): array
    {
        return [
            'view'        => ['dungeonroute.viewold', 'dungeonroute.view', [], []],
            'view floor'  => ['dungeonroute.viewold.floor', 'dungeonroute.view.floor', ['floorIndex' => '2'], ['floorIndex' => '2']],
            'embed'       => ['dungeonroute.embedold', 'dungeonroute.embed', [], ['floorIndex' => '1']],
            'embed floor' => ['dungeonroute.embedold.floor', 'dungeonroute.embed', ['floorIndex' => '2'], ['floorIndex' => '2']],
            'preview'     => ['dungeonroute.previewold', 'dungeonroute.preview', ['floorIndex' => '2'], ['floorIndex' => '2']],
            'edit'        => ['dungeonroute.editold', 'dungeonroute.edit', [], []],
            'edit floor'  => ['dungeonroute.editold.floor', 'dungeonroute.edit.floor', ['floorIndex' => '2'], ['floorIndex' => '2']],
            'clone'       => ['dungeonroute.cloneold', 'dungeonroute.clone', [], []],
            'claim'       => ['dungeonroute.claimold', 'dungeonroute.claim', [], []],
        ];
    }
}
