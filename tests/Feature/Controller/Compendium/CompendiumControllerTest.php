<?php

namespace Tests\Feature\Controller\Compendium;

use App\Models\Dungeon;
use App\Models\Spell\SpellTuningBuild;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
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

    #[Test]
    public function index_givenBuildWithoutChanges_countsItAsComparedBuild(): void
    {
        // Arrange
        $build = SpellTuningBuild::factory()->create(['to_build' => '0.0.0.00096', 'to_build_number' => 96]);
        Cache::forget('compendium.index.stats');

        try {
            // Act
            $response = $this->get(route('compendium.index'));

            // Assert
            $response->assertOk();
            $response->assertSeeText(sprintf('%s %s', number_format(SpellTuningBuild::query()->count()), __('view_compendium.index.cards.tuning.count_suffix')));
        } finally {
            $build->delete();
            Cache::forget('compendium.index.stats');
        }
    }
}
