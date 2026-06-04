<?php

namespace Tests\Feature\Controller\Compendium;

use App\Features\NpcCompendium;
use App\Models\Dungeon;
use App\Models\Enemy;
use App\Models\Npc\Npc;
use App\Models\User;
use Laravel\Pennant\Feature;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('Compendium')]
final class NpcCompendiumControllerTest extends PublicTestCase
{
    /** @var array<string, mixed> */
    private array $datatableParams = [
        'draw'    => 1,
        'start'   => 0,
        'length'  => 25,
        'search'  => ['value' => ''],
        'columns' => [
            ['name' => 'name',       'search' => ['value' => ''], 'searchable' => 'true',  'orderable' => 'true'],
            ['name' => 'dungeon_id', 'search' => ['value' => ''], 'searchable' => 'true',  'orderable' => 'true'],
            ['name' => 'spells',     'search' => ['value' => ''], 'searchable' => 'false', 'orderable' => 'false'],
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
        $response = $this->get(route('npc.compendium.index'));

        // Assert
        $response->assertNotFound();
    }

    #[Test]
    public function index_givenAdminFeatureDisabled_returnsNotFound(): void
    {
        // Arrange
        Feature::define(NpcCompendium::class, false);

        // Act
        $response = $this->get(route('npc.compendium.index'));

        // Assert
        $response->assertNotFound();
    }

    #[Test]
    public function index_givenNoAuthFeatureEnabled_returnsOk(): void
    {
        // Arrange
        $this->actingAsGuest();

        // Act
        $response = $this->get(route('npc.compendium.index'));

        // Assert
        $response->assertOk();
    }

    #[Test]
    public function index_givenAdminFeatureEnabled_returnsOk(): void
    {
        // Act
        $response = $this->get(route('npc.compendium.index'));

        // Assert
        $response->assertOk();
    }

    #[Test]
    public function index_givenShowSeasons_returnsNoDuplicateDungeonsInSelect(): void
    {
        // Act
        $response = $this->get(route('npc.compendium.index'));

        // Assert
        $response->assertOk();
        $dom = new \DOMDocument();
        @$dom->loadHTML($response->getContent());
        $xpath   = new \DOMXPath($dom);
        $options = $xpath->query('//select[@id="compendium_filter_dungeon"]//option');
        $values  = [];
        foreach ($options as $option) {
            if (!$option instanceof \DOMElement) {
                continue;
            }
            $value = $option->getAttribute('value');
            if (is_numeric($value) && (int)$value > 0) {
                $values[] = (int)$value;
            }
        }

        $this->assertSame(count($values), count(array_unique($values)), 'Dungeon select contains duplicate dungeon IDs');
    }

    #[Test]
    public function get_givenNoDungeonId_returnsUnprocessableContent(): void
    {
        // Act
        $response = $this->call('GET', route('ajax.npc.compendium.search'), $this->datatableParams, [], [], [
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
        ]);

        // Assert
        $response->assertUnprocessable();
    }

    #[Test]
    public function get_givenDungeonFilter_returnsOnlyNpcsForDungeon(): void
    {
        // Arrange
        $dungeon        = Dungeon::active()->first();
        $mappingVersion = $dungeon->getCurrentMappingVersion();
        $this->assertNotNull($mappingVersion);

        // Act
        $response = $this->call('GET', route('ajax.npc.compendium.search'), array_merge($this->datatableParams, [
            'dungeon_id' => $dungeon->id,
        ]), [], [], ['HTTP_X-Requested-With' => 'XMLHttpRequest']);

        // Assert
        $response->assertOk();
        $data = $response->json();
        $this->assertArrayHasKey('data', $data);
        foreach ($data['data'] as $npc) {
            $this->assertTrue(
                Enemy::where('npc_id', $npc['id'])
                    ->where('mapping_version_id', $mappingVersion->id)
                    ->exists(),
            );
        }
    }

    #[Test]
    public function get_givenNonExistentDungeonId_returnsUnprocessableContent(): void
    {
        // Arrange — dungeon_id = -1 does not exist in the dungeons table
        $params = array_merge($this->datatableParams, ['dungeon_id' => -1]);

        // Act
        $response = $this->call('GET', route('ajax.npc.compendium.search'), $params, [], [], [
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
        ]);

        // Assert
        $response->assertUnprocessable();
    }

    #[Test]
    public function show_givenValidNpc_returnsOk(): void
    {
        // Arrange
        $npc = Npc::with('classification')->first();
        $this->assertNotNull($npc);

        // Act
        $response = $this->get(route('npc.compendium.show', $npc));

        // Assert
        $response->assertOk();
        $response->assertSeeText(__($npc->name));
    }

    #[Test]
    public function show_givenCorrectSlug_returnsOk(): void
    {
        // Arrange
        $npc = Npc::with('classification')->first();
        $this->assertNotNull($npc);

        // Act
        $response = $this->get(route('npc.compendium.show', $npc));

        // Assert
        $response->assertOk();
    }

    #[Test]
    public function show_givenIdOnly_redirectsToCanonicalUrl(): void
    {
        // Arrange
        $npc = Npc::first();
        $this->assertNotNull($npc);

        // Act
        $response = $this->get(sprintf('/compendium/npc/%d', $npc->id));

        // Assert
        $response->assertRedirect(route('npc.compendium.show', $npc));
        $response->assertStatus(301);
    }

    #[Test]
    public function show_givenWrongSlug_redirectsToCanonicalUrl(): void
    {
        // Arrange
        $npc = Npc::first();
        $this->assertNotNull($npc);

        // Act
        $response = $this->get(sprintf('/compendium/npc/%d-wrong-slug', $npc->id));

        // Assert
        $response->assertRedirect(route('npc.compendium.show', $npc));
        $response->assertStatus(301);
    }

    #[Test]
    public function show_givenInvalidNpc_returnsNotFound(): void
    {
        // Act
        $response = $this->get(route('npc.compendium.show', ['npc' => 0]));

        // Assert
        $response->assertNotFound();
    }

    #[Test]
    public function get_givenNameSearch_returnsValidResponse(): void
    {
        // Arrange
        $dungeon                   = Dungeon::active()->first();
        $params                    = array_merge($this->datatableParams, ['dungeon_id' => $dungeon->id]);
        $params['search']['value'] = 'a';

        // Act
        $response = $this->call('GET', route('ajax.npc.compendium.search'), $params, [], [], [
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
        ]);

        // Assert
        $response->assertOk();
        $data = $response->json();
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('recordsTotal', $data);
    }
}
