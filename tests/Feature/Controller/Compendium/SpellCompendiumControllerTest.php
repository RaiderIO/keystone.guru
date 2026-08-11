<?php

namespace Tests\Feature\Controller\Compendium;

use App\Features\NpcCompendium;
use App\Models\Dungeon;
use App\Models\GameVersion\GameVersion;
use App\Models\Spell\Spell;
use App\Models\Spell\SpellDungeon;
use App\Models\User;
use Laravel\Pennant\Feature;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\ReadsDungeonSelect;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('Compendium')]
final class SpellCompendiumControllerTest extends PublicTestCase
{
    use ReadsDungeonSelect;

    /** @var array<string, mixed> */
    private array $datatableParams = [
        'draw'    => 1,
        'start'   => 0,
        'length'  => 25,
        'search'  => ['value' => ''],
        'columns' => [
            ['name' => 'name',      'search' => ['value' => ''], 'searchable' => 'true',  'orderable' => 'true'],
            ['name' => 'dungeon_id', 'search' => ['value' => ''], 'searchable' => 'false', 'orderable' => 'false'],
            ['name' => 'npcs',      'search' => ['value' => ''], 'searchable' => 'false', 'orderable' => 'false'],
        ],
    ];

    #[\Override]
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
        $response = $this->get(route('spell.compendium.index.dungeon', ['dungeon' => Dungeon::active()->firstOrFail()]));

        // Assert
        $response->assertOk();
    }

    #[Test]
    public function index_givenAdminFeatureEnabled_returnsOk(): void
    {
        // Act
        $response = $this->get(route('spell.compendium.index.dungeon', ['dungeon' => Dungeon::active()->firstOrFail()]));

        // Assert
        $response->assertOk();
    }

    #[Test]
    public function index_givenNoDungeonInUrl_redirectsToContextDungeon(): void
    {
        // Arrange
        $dungeon           = Dungeon::active()->firstOrFail();
        $user              = User::findOrFail(1);
        $originalDungeonId = $user->dungeon_id;
        $user->dungeon_id  = $dungeon->id;
        $user->save();

        try {
            // Act
            $response = $this->actingAs($user->fresh())->get(route('spell.compendium.index'));

            // Assert - a 302, not a 301: the target depends on the visitor's own context dungeon
            $response->assertRedirect(route('spell.compendium.index.dungeon', ['dungeon' => $dungeon]));
            $response->assertStatus(302);
        } finally {
            $user->dungeon_id = $originalDungeonId;
            $user->save();
        }
    }

    #[Test]
    public function indexDungeon_givenDungeonOtherThanContextDungeon_rendersThatDungeonAndMakesItTheContext(): void
    {
        // Arrange - two different dungeons, so the URL is provably what decides what is rendered
        $contextDungeon   = Dungeon::active()->orderBy('id')->firstOrFail();
        $requestedDungeon = Dungeon::active()->where('id', '!=', $contextDungeon->id)->orderBy('id')->firstOrFail();

        $user              = User::findOrFail(1);
        $originalDungeonId = $user->dungeon_id;
        $user->dungeon_id  = $contextDungeon->id;
        $user->save();

        try {
            // Act
            $response = $this->actingAs($user->fresh())->get(route('spell.compendium.index.dungeon', ['dungeon' => $requestedDungeon]));

            // Assert
            $response->assertOk();
            $this->assertSame($requestedDungeon->id, $this->getSelectedDungeonId($response->getContent()));
            $this->assertSame($requestedDungeon->id, User::findOrFail(1)->dungeon_id);
        } finally {
            $user->dungeon_id = $originalDungeonId;
            $user->save();
        }
    }

    #[Test]
    public function indexDungeon_givenDungeonNotOfferedByTheFilter_stillDrivesTheTableFromTheUrlDungeon(): void
    {
        // Arrange - the dungeon filter only lists dungeons mapped for the visitor's game version, so
        // a dungeon outside it is not among its options. The table must still show the URL's dungeon
        $gameVersion = GameVersion::getUserOrDefaultGameVersion();
        $dungeon     = Dungeon::query()
            ->whereDoesntHave('mappingVersions', static fn($query) => $query->where('game_version_id', $gameVersion->id))
            ->firstOrFail();

        // Act
        $response = $this->get(route('spell.compendium.index.dungeon', ['dungeon' => $dungeon]));

        // Assert
        $response->assertOk();
        $this->assertNotSame($dungeon->id, $this->getSelectedDungeonId($response->getContent()));
        $response->assertSee(sprintf('const contextDungeonId = %d;', $dungeon->id), false);
    }

    #[Test]
    public function indexDungeon_givenUnknownDungeonSlug_returnsNotFound(): void
    {
        // Act
        $response = $this->get('/compendium/spell/dungeon/not-a-dungeon');

        // Assert
        $response->assertNotFound();
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
    public function show_givenCorrectSlug_returnsOk(): void
    {
        // Arrange
        $spell = Spell::where('hidden_on_map', false)->first();
        $this->assertNotNull($spell);

        // Act
        $response = $this->get(route('spell.compendium.show', $spell));

        // Assert
        $response->assertOk();
    }

    #[Test]
    public function show_givenIdOnly_redirectsToCanonicalUrl(): void
    {
        // Arrange
        $spell = Spell::where('hidden_on_map', false)->first();
        $this->assertNotNull($spell);

        // Act
        $response = $this->get(sprintf('/compendium/spell/%d', $spell->id));

        // Assert
        $response->assertRedirect(route('spell.compendium.show', $spell));
        $response->assertStatus(301);
    }

    #[Test]
    public function show_givenWrongSlug_redirectsToCanonicalUrl(): void
    {
        // Arrange
        $spell = Spell::where('hidden_on_map', false)->first();
        $this->assertNotNull($spell);

        // Act
        $response = $this->get(sprintf('/compendium/spell/%d-wrong-slug', $spell->id));

        // Assert
        $response->assertRedirect(route('spell.compendium.show', $spell));
        $response->assertStatus(301);
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
