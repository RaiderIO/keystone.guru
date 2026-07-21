<?php

namespace Tests\Feature\Controller\Compendium;

use App\Features\NpcCompendium;
use App\Models\User;
use Laravel\Pennant\Feature;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('Compendium')]
final class CompendiumControllerTest extends PublicTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::findOrFail(1));
        Feature::define(NpcCompendium::class, true);
    }

    #[Test]
    public function index_givenFeatureDisabled_returnsNotFound(): void
    {
        // Arrange
        Feature::define(NpcCompendium::class, false);

        // Act
        $response = $this->get(route('compendium.index'));

        // Assert
        $response->assertNotFound();
    }

    #[Test]
    public function index_givenFeatureEnabled_returnsOk(): void
    {
        // Act
        $response = $this->get(route('compendium.index'));

        // Assert
        $response->assertOk();
    }

    #[Test]
    public function index_givenFeatureEnabled_displaysSectionLinksAndRaiderIoCta(): void
    {
        // Act
        $response = $this->get(route('compendium.index'));

        // Assert
        $response->assertOk();
        $response->assertSee(route('npc.compendium.index'));
        $response->assertSee(route('spell.compendium.index'));
        $response->assertSee(route('compendium.activity.index'));
        $response->assertSee(route('compendium.class.index'));
        $response->assertSee('https://raider.io/addon');
    }
}
