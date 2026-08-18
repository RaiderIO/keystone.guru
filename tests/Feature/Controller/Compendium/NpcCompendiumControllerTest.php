<?php

namespace Tests\Feature\Controller\Compendium;

use App\Features\NpcCompendium;
use App\Models\Dungeon;
use App\Models\Enemy;
use App\Models\GameVersion\GameVersion;
use App\Models\Mapping\MappingVersion;
use App\Models\Npc\Npc;
use App\Models\Npc\NpcClassification;
use App\Models\Season;
use App\Models\User;
use App\Service\View\RequestViewContextInterface;
use App\Service\View\ViewServiceInterface;
use Laravel\Pennant\Feature;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\ProvidesDungeon;
use Tests\Feature\Traits\ReadsDungeonSelect;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('Compendium')]
final class NpcCompendiumControllerTest extends PublicTestCase
{
    use ProvidesDungeon;
    use ReadsDungeonSelect;

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
        $response = $this->get(route('npc.compendium.index.dungeon', ['dungeon' => Dungeon::active()->firstOrFail()]));

        // Assert
        $response->assertOk();
    }

    #[Test]
    public function index_givenAdminFeatureEnabled_returnsOk(): void
    {
        // Act
        $response = $this->get(route('npc.compendium.index.dungeon', ['dungeon' => Dungeon::active()->firstOrFail()]));

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
            $response = $this->actingAs($user->fresh())->get(route('npc.compendium.index'));

            // Assert - a 302, not a 301: the target depends on the visitor's own context dungeon
            $response->assertRedirect(route('npc.compendium.index.dungeon', ['dungeon' => $dungeon]));
            $response->assertStatus(302);
        } finally {
            $user->dungeon_id = $originalDungeonId;
            $user->save();
        }
    }

    #[Test]
    public function indexDungeon_givenDungeonOtherThanContextDungeon_rendersThatDungeonAndMakesItTheContext(): void
    {
        // Arrange - two different dungeons, so the URL is provably what decides what is rendered. Both must
        // be ones the filter actually offers, or no option renders as selected and there is nothing to read.
        $contextDungeon   = $this->dungeonsOfferedByDungeonSelect()->orderBy('id')->firstOrFail();
        $requestedDungeon = $this->dungeonsOfferedByDungeonSelect()->where('id', '!=', $contextDungeon->id)->orderBy('id')->firstOrFail();

        $user              = User::findOrFail(1);
        $originalDungeonId = $user->dungeon_id;
        $user->dungeon_id  = $contextDungeon->id;
        $user->save();

        try {
            // Act
            $response = $this->actingAs($user->fresh())->get(route('npc.compendium.index.dungeon', ['dungeon' => $requestedDungeon]));

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
        $response = $this->get(route('npc.compendium.index.dungeon', ['dungeon' => $dungeon]));

        // Assert
        $response->assertOk();
        $this->assertNoDungeonSelected($response->getContent());
        $response->assertSee(sprintf('const contextDungeonId = %d;', $dungeon->id), false);
    }

    #[Test]
    public function indexDungeon_givenUnknownDungeonSlug_returnsNotFound(): void
    {
        // Act
        $response = $this->get('/compendium/dungeon/not-a-dungeon/npc');

        // Assert
        $response->assertNotFound();
    }

    #[Test]
    public function index_givenShowSeasons_returnsNoDuplicateDungeonsInSelect(): void
    {
        // Arrange - the duplicate this guards against can only arise for a dungeon that renders in a season
        // optgroup *and* in its expansion's, which `common/dungeon/select` prevents by rejecting the season
        // dungeons from the expansion groups. That branch is skipped whole when the visitor's game version
        // has no seasons, so without these two preconditions "unique" would hold for a reason unrelated to
        // the scenario in the name
        $this->assertTrue(
            (bool)GameVersion::getUserOrDefaultGameVersion()->has_seasons,
            'The visitor game version has no seasons, so the select never builds the season optgroups this test is about',
        );

        // Sourced exactly as DungeonSelectComposer feeds the blade, next season included: a season seeded
        // ahead of its start date is normal here, and reading only the current one would let the
        // intersection below go empty while the scenario is live through the next
        $region      = app(RequestViewContextInterface::class)->getUserOrDefaultRegion();
        $viewService = app(ViewServiceInterface::class);

        $seasonDungeonIds = collect([
            $viewService->getNextSeasonForRegion($region),
            $viewService->getCurrentSeasonForRegion($region),
        ])->filter()
            ->flatMap(static fn(Season $season) => $season->dungeons->pluck('id'))
            ->unique()
            ->all();

        // Act
        $response = $this->get(route('npc.compendium.index.dungeon', ['dungeon' => Dungeon::active()->firstOrFail()]));

        // Assert
        $response->assertOk();
        $dungeonIds = $this->getOfferedDungeonIds($response->getContent());

        $this->assertNotEmpty(
            array_intersect($seasonDungeonIds, $dungeonIds),
            'The select rendered no season dungeon, so no dungeon could have landed in two optgroups',
        );
        $this->assertSame(count($dungeonIds), count(array_unique($dungeonIds)), 'Dungeon select contains duplicate dungeon IDs');
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
        // Arrange - the endpoint joins npcs through enemies, so a dungeon whose mapping version carries no
        // enemy with an npc returns an empty set and the loop below then asserts nothing at all. Require the
        // property rather than hoping the pick happens to have it
        [$dungeon, $mappingVersion] = $this->findDungeon(
            dungeonActive: true,
            minEnemies:    1,
            resolve:       static fn(Dungeon $dungeon, MappingVersion $mappingVersion) => $mappingVersion->enemies()->whereNotNull('npc_id')->exists() ?: null,
        );

        // Act
        $response = $this->call('GET', route('ajax.npc.compendium.search'), array_merge($this->datatableParams, [
            'dungeon_id' => $dungeon->id,
        ]), [], [], ['HTTP_X-Requested-With' => 'XMLHttpRequest']);

        // Assert
        $response->assertOk();
        $data = $response->json();
        $this->assertArrayHasKey('data', $data);
        $this->assertNotEmpty($data['data'], 'The dungeon filter returned no NPCs, so the check below proves nothing');
        foreach ($data['data'] as $npc) {
            $this->assertTrue(
                Enemy::where('npc_id', $npc['id'])
                    ->where('mapping_version_id', $mappingVersion->id)
                    ->exists(),
            );
        }
    }

    #[Test]
    public function get_givenDungeonFilter_returnsTooltipDataPerNpc(): void
    {
        // Arrange - the datatable builds each NPC link's hover tooltip off this payload (#4096); it
        // is not an appended attribute, so the endpoint has to ask for it explicitly
        [$dungeon, $mappingVersion] = $this->findDungeon(
            dungeonActive: true,
            minEnemies:    1,
            resolve:       static fn(Dungeon $dungeon, MappingVersion $mappingVersion) => $mappingVersion->enemies()->whereNotNull('npc_id')->exists() ?: null,
        );

        // Act
        $response = $this->call('GET', route('ajax.npc.compendium.search'), array_merge($this->datatableParams, [
            'dungeon_id' => $dungeon->id,
        ]), [], [], ['HTTP_X-Requested-With' => 'XMLHttpRequest']);

        // Assert
        $response->assertOk();
        $data = $response->json();
        $this->assertNotEmpty($data['data'], 'The dungeon filter returned no NPCs, so the check below proves nothing');
        foreach ($data['data'] as $npc) {
            $this->assertArrayHasKey('tooltip_data', $npc);
            // The name is the one row every NPC has; everything else is left out when it says nothing
            $this->assertArrayHasKey('name', $npc['tooltip_data']);
        }
    }

    #[Test]
    public function get_givenDungeonWithAClassificationlessNpc_returnsOk(): void
    {
        // Arrange - a few seeded NPCs carry a classification_id that matches no npc_classifications
        // row, and some of those are enemies on a current mapping version. Building their tooltip
        // reads that relation, so this dungeon is what proves the payload survives a null one.
        $classificationlessNpcIds = Npc::query()
            ->whereNotIn('classification_id', NpcClassification::query()->select('id'))
            ->pluck('id');

        $dungeon = Dungeon::all()->first(static function (Dungeon $dungeon) use ($classificationlessNpcIds): bool {
            $mappingVersion = $dungeon->getCurrentMappingVersion();

            return $mappingVersion !== null && $mappingVersion->enemies()->whereIn('npc_id', $classificationlessNpcIds)->exists();
        });

        if ($dungeon === null) {
            $this->markTestSkipped('No mapped NPC carries a classification_id without a matching classification row');
        }

        // Act - a page long enough to reach it: the endpoint orders by classification_id descending,
        // so an NPC whose classification does not resolve sorts to the very end of the dungeon
        $response = $this->call('GET', route('ajax.npc.compendium.search'), array_merge($this->datatableParams, [
            'dungeon_id' => $dungeon->id,
            'length'     => 500,
        ]), [], [], ['HTTP_X-Requested-With' => 'XMLHttpRequest']);

        // Assert
        $response->assertOk();

        $rowsById           = array_column($response->json()['data'], null, 'id');
        $classificationless = array_intersect_key($rowsById, array_flip($classificationlessNpcIds->all()));

        $this->assertNotEmpty($classificationless, 'The response carried no classificationless NPC, so it proves nothing');
        foreach ($classificationless as $npc) {
            $this->assertArrayNotHasKey('classification', $npc['tooltip_data']);
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
    public function show_givenValidNpc_seesWowheadLink(): void
    {
        // Arrange
        $npc = Npc::with('classification')->first();
        $this->assertNotNull($npc);

        // Act
        $response = $this->get(route('npc.compendium.show', $npc));

        // Assert
        $response->assertOk();
        $response->assertSee($npc->wowhead_url, false);
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
    public function show_givenClassificationlessNpc_returnsOk(): void
    {
        // Arrange - a few seeded NPCs carry a classification_id that matches no npc_classifications
        // row (#4115); the header partial reads that relation directly, unlike the ajax endpoint's
        // tooltip_data, so this is what proves the page survives a null one.
        $npc = Npc::query()
            ->whereNotIn('classification_id', NpcClassification::query()->select('id'))
            ->first();

        if ($npc === null) {
            $this->markTestSkipped('No NPC carries a classification_id without a matching classification row');
        }

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
