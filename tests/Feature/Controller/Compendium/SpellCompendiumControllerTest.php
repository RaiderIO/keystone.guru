<?php

namespace Tests\Feature\Controller\Compendium;

use App\Features\NpcCompendium;
use App\Models\Dungeon;
use App\Models\Spell\Spell;
use App\Models\Spell\SpellDungeon;
use App\Models\User;
use Laravel\Pennant\Feature;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('Compendium')]
final class SpellCompendiumControllerTest extends PublicTestCase
{
    /** @var array<string, mixed> */
    private array $datatableParams = [
        'draw'    => 1,
        'start'   => 0,
        'length'  => 25,
        'search'  => ['value' => ''],
        'columns' => [
            ['name' => 'name',      'search' => ['value' => ''], 'searchable' => 'true',  'orderable' => 'true'],
            ['name' => 'dungeon_id', 'search' => ['value' => ''], 'searchable' => 'false', 'orderable' => 'false'],
            ['name' => 'npc_names', 'search' => ['value' => ''], 'searchable' => 'false', 'orderable' => 'false'],
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::findOrFail(1));
        Feature::define(NpcCompendium::class, true);
    }

    #[Test]
    public function index_givenNoAuthFeatureDisabled_returnsNotFound(): void
    {
        // Arrange
        $this->actingAsGuest();
        Feature::define(NpcCompendium::class, false);

        // Act
        $response = $this->get(route('spell.compendium.index'));

        // Assert
        $response->assertNotFound();
    }

    #[Test]
    public function index_givenAdminFeatureDisabled_returnsNotFound(): void
    {
        // Arrange
        Feature::define(NpcCompendium::class, false);

        // Act
        $response = $this->get(route('spell.compendium.index'));

        // Assert
        $response->assertNotFound();
    }

    #[Test]
    public function index_givenNoAuthFeatureEnabled_returnsOk(): void
    {
        // Arrange
        $this->actingAsGuest();

        // Act
        $response = $this->get(route('spell.compendium.index'));

        // Assert
        $response->assertOk();
    }

    #[Test]
    public function index_givenAdminFeatureEnabled_returnsOk(): void
    {
        // Act
        $response = $this->get(route('spell.compendium.index'));

        // Assert
        $response->assertOk();
    }

    #[Test]
    public function show_givenValidSpell_returnsOk(): void
    {
        // Arrange
        $spell = Spell::where('hidden_on_map', false)->first();
        $this->assertNotNull($spell);

        // Act
        $response = $this->get(route('spell.compendium.show', $spell));

        // Assert
        $response->assertOk();
        $response->assertSeeText(__($spell->name));
    }

    #[Test]
    public function show_givenValidSpell_rendersEventFeedSection(): void
    {
        // Arrange
        $spell = Spell::where('hidden_on_map', false)->first();
        $this->assertNotNull($spell);

        // Act
        $response = $this->get(route('spell.compendium.show', $spell));

        // Assert
        $response->assertOk();
        $response->assertSeeText(__('view_compendium.spell.sections.event_feed.title'));
    }

    #[Test]
    public function show_givenInvalidSpell_returnsNotFound(): void
    {
        // Act
        $response = $this->get(route('spell.compendium.show', ['spell' => 0]));

        // Assert
        $response->assertNotFound();
    }

    #[Test]
    public function show_givenHiddenSpell_returnsNotFound(): void
    {
        // Arrange
        $spell = Spell::where('hidden_on_map', true)->first();
        $this->assertNotNull($spell);

        // Act
        $response = $this->get(route('spell.compendium.show', $spell));

        // Assert
        $response->assertNotFound();
    }

    #[Test]
    public function get_givenNoDungeonId_returnsAllSpells(): void
    {
        // Act
        $response = $this->call('GET', route('ajax.spell.compendium.search'), $this->datatableParams, [], [], [
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
        ]);

        // Assert
        $response->assertOk();
        $data = $response->json();
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('recordsTotal', $data);
        $this->assertGreaterThan(0, $data['recordsTotal']);
    }

    #[Test]
    public function get_givenNonExistentDungeonId_returnsUnprocessableContent(): void
    {
        // Arrange
        $params = array_merge($this->datatableParams, ['dungeon_id' => -1]);

        // Act
        $response = $this->call('GET', route('ajax.spell.compendium.search'), $params, [], [], [
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
        ]);

        // Assert
        $response->assertUnprocessable();
    }

    #[Test]
    public function get_givenDungeonFilter_returnsOnlySpellsForDungeon(): void
    {
        // Arrange
        $dungeon = Dungeon::active()->first();
        $this->assertNotNull($dungeon);

        // Act
        $response = $this->call('GET', route('ajax.spell.compendium.search'), array_merge($this->datatableParams, [
            'dungeon_id' => $dungeon->id,
        ]), [], [], ['HTTP_X-Requested-With' => 'XMLHttpRequest']);

        // Assert
        $response->assertOk();
        $data = $response->json();
        $this->assertArrayHasKey('data', $data);
        foreach ($data['data'] as $spell) {
            $this->assertTrue(
                SpellDungeon::where('spell_id', $spell['id'])
                    ->where('dungeon_id', $dungeon->id)
                    ->exists(),
            );
        }
    }

    #[Test]
    public function get_givenNameSearch_returnsValidResponse(): void
    {
        // Arrange
        $params                    = $this->datatableParams;
        $params['search']['value'] = 'a';

        // Act
        $response = $this->call('GET', route('ajax.spell.compendium.search'), $params, [], [], [
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
        ]);

        // Assert
        $response->assertOk();
        $data = $response->json();
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('recordsTotal', $data);
    }
}
