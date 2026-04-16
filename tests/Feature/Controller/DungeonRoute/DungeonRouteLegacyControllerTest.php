<?php

namespace Controller\DungeonRoute;

use App\Models\User;
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
}
