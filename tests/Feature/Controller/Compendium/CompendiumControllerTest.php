<?php

namespace Tests\Feature\Controller\Compendium;

use App\Models\Dungeon;
use App\Models\User;
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
    }

    #[Test]
    public function index_givenAdmin_returnsOk(): void
    {
        // Act
        $response = $this->get(route('compendium.index'));

        // Assert
        $response->assertOk();
    }

    #[Test]
    public function index_givenAdmin_displaysSectionLinksAndRaiderIoCta(): void
    {
        // Act
        $response = $this->get(route('compendium.index'));

        // Assert
        $response->assertOk();
        $contextDungeon = Dungeon::getUserOrDefaultDungeon();
        $response->assertSee(route('npc.compendium.index.dungeon', ['dungeon' => $contextDungeon]));
        $response->assertSee(route('spell.compendium.index.dungeon', ['dungeon' => $contextDungeon]));
        $response->assertSee(route('compendium.activity.index'));
        $response->assertSee(route('compendium.class.index'));
        $response->assertSee('https://raider.io/addon');
    }
}
