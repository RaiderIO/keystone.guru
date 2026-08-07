<?php

namespace Tests\Feature\Controller\Compendium;

use App\Features\NpcCompendium;
use App\Models\Dungeon;
use App\Models\User;
use App\Service\Season\SeasonServiceInterface;
use Laravel\Pennant\Feature;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('Compendium')]
final class NpcCompendiumControllerActivityTest extends PublicTestCase
{
    private Dungeon $dungeon;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::findOrFail(1));
        Feature::define(NpcCompendium::class, true);

        // Must be a dungeon of the CURRENT season - that is what the controller scopes to, and it
        // redirects away from anything else. Taking the highest season id instead breaks as soon as an
        // upcoming season is seeded ahead of its start date, which is exactly what happens every time a
        // new season's mapping is prepared in advance.
        $this->dungeon = app(SeasonServiceInterface::class)->getCurrentSeason()->dungeons()->first();
    }

    #[Test]
    public function activityIndex_givenFeatureDisabled_returnsNotFound(): void
    {
        // Arrange
        Feature::define(NpcCompendium::class, false);

        // Act
        $response = $this->get(route('compendium.activity.index'));

        // Assert
        $response->assertNotFound();
    }

    #[Test]
    public function activityIndex_givenFeatureEnabled_redirectsToDungeon(): void
    {
        // Act
        $response = $this->get(route('compendium.activity.index'));

        // Assert
        $response->assertRedirect();
    }

    #[Test]
    public function activity_givenFeatureEnabled_returnsOk(): void
    {
        // Act
        $response = $this->get(route('compendium.activity', $this->dungeon));

        // Assert
        $response->assertOk();
    }

    #[Test]
    public function activity_givenFeatureEnabledNoAuth_returnsOk(): void
    {
        // Arrange
        $this->actingAsGuest();

        // Act
        $response = $this->get(route('compendium.activity', $this->dungeon));

        // Assert
        $response->assertOk();
    }

    #[Test]
    public function activityDay_givenValidDate_returnsOk(): void
    {
        // Act
        $response = $this->get(route('compendium.activity.day', ['dungeon' => $this->dungeon, 'date' => '2025-01-15']));

        // Assert
        $response->assertOk();
    }

    #[Test]
    public function activityDay_givenInvalidDateFormat_returnsNotFound(): void
    {
        // Act
        $response = $this->get(sprintf('/compendium/activity/%s/not-a-date', $this->dungeon->slug));

        // Assert
        $response->assertNotFound();
    }

    #[Test]
    public function activityDay_givenFeatureDisabled_returnsNotFound(): void
    {
        // Arrange
        Feature::define(NpcCompendium::class, false);

        // Act
        $response = $this->get(route('compendium.activity.day', ['dungeon' => $this->dungeon, 'date' => '2025-01-15']));

        // Assert
        $response->assertNotFound();
    }
}
