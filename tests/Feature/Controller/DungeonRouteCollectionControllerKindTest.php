<?php

namespace Tests\Feature\Controller;

use App\Features\CreatorProfiles;
use App\Http\Requests\DungeonRoute\DungeonRouteCollectionCreateFormRequest;
use App\Http\Requests\DungeonRoute\DungeonRouteCollectionIndexFormRequest;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteCollection;
use App\Models\DungeonRoute\DungeonRouteCollectionRoute;
use App\Models\GameVersion\GameVersion;
use App\Models\Laratrust\Role;
use App\Models\Mapping\MappingVersion;
use App\Models\PublishedState;
use App\Models\Season;
use App\Models\User;
use App\Service\DungeonRoute\Dtos\DungeonRouteCollectionGroup;
use App\Service\Season\SeasonServiceInterface;
use Database\Factories\DungeonRoute\DungeonRouteCollectionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Laravel\Pennant\Feature;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Traits\CreatesSeason;
use Tests\TestCases\PublicTestCase;

/**
 * A collection's game version and optional season: who may join it, how its routes are grouped, and how the
 * overview filters and sorts collections by them.
 */
#[Group('Controller')]
final class DungeonRouteCollectionControllerKindTest extends PublicTestCase
{
    use CreatesSeason;

    private ?User $creator = null;

    /** @var array<int, DungeonRoute> */
    private array $createdDungeonRoutes = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->beforeApplicationDestroyed(function (): void {
            DungeonRouteCollection::query()
                ->whereIn('user_id', array_filter([$this->creator?->id]))
                ->get()
                ->each(static fn(DungeonRouteCollection $dungeonRouteCollection) => $dungeonRouteCollection->delete());

            foreach ($this->createdDungeonRoutes as $dungeonRoute) {
                $dungeonRoute->delete();
            }

            if ($this->creator !== null) {
                Feature::for($this->creator)->forget(CreatorProfiles::class);
                $this->creator->delete();
            }
        });
    }

    #[Test]
    public function savenew_givenACurrentSeasonOfTheGameVersion_createsASeasonSet(): void
    {
        // Arrange
        $creator       = $this->creator();
        $currentSeason = $this->currentRetailSeason();

        // Act
        $response = $this->actingAs($creator)->post(route('collections.savenew'), [
            'name'            => 'ZzTestSeasonSet',
            'published_state' => PublishedState::WORLD,
            'season_id'       => $currentSeason->id,
        ]);

        // Assert
        $response->assertSessionHasNoErrors();
        $dungeonRouteCollection = DungeonRouteCollection::query()->where('user_id', $creator->id)->firstOrFail();
        $this->assertSame($this->retail()->id, $dungeonRouteCollection->game_version_id);
        $this->assertSame($currentSeason->id, $dungeonRouteCollection->season_id);
        $this->assertTrue($dungeonRouteCollection->isSeasonSet());
    }

    #[Test]
    public function savenew_givenNoSeason_createsAFreeFormCollection(): void
    {
        // Arrange
        $creator = $this->creator();

        // Act
        $response = $this->actingAs($creator)->post(route('collections.savenew'), [
            'name'            => 'ZzTestFreeForm',
            'published_state' => PublishedState::WORLD,
            'season_id'       => null,
        ]);

        // Assert
        $response->assertSessionHasNoErrors();
        $dungeonRouteCollection = DungeonRouteCollection::query()->where('user_id', $creator->id)->firstOrFail();
        $this->assertSame($this->retail()->id, $dungeonRouteCollection->game_version_id);
        $this->assertNull($dungeonRouteCollection->season_id);
    }

    #[Test]
    public function savenew_givenAnyPayload_takesTheGameVersionSelectedOnTheSite(): void
    {
        // Arrange
        $creator     = $this->creator();
        $gameVersion = $this->gameVersionWithoutSeasons();
        $creator->update(['game_version_id' => $gameVersion->id]);

        // Act
        $response = $this->actingAs($creator)->post(route('collections.savenew'), [
            'name'            => 'ZzTestSitesGameVersion',
            'published_state' => PublishedState::WORLD,
        ]);

        // Assert
        $response->assertSessionHasNoErrors();
        $this->assertSame($gameVersion->id, DungeonRouteCollection::query()->where('user_id', $creator->id)->firstOrFail()->game_version_id);
    }

    #[Test]
    public function savenew_givenAPostedGameVersion_ignoresIt(): void
    {
        // Arrange
        $creator = $this->creator();

        // Act
        $response = $this->actingAs($creator)->post(route('collections.savenew'), [
            'name'            => 'ZzTestPostedGameVersion',
            'published_state' => PublishedState::WORLD,
            'game_version_id' => $this->gameVersionWithoutSeasons()->id,
        ]);

        // Assert
        $response->assertSessionHasNoErrors();
        $this->assertSame($this->retail()->id, DungeonRouteCollection::query()->where('user_id', $creator->id)->firstOrFail()->game_version_id);
    }

    #[Test]
    public function savenew_givenASeasonOnAGameVersionWithoutSeasons_failsValidation(): void
    {
        // Arrange
        $creator     = $this->creator();
        $gameVersion = $this->gameVersionWithoutSeasons();
        $season      = $this->createSeason(['expansion_id' => $gameVersion->expansion_id]);
        $creator->update(['game_version_id' => $gameVersion->id]);

        // Act
        $response = $this->actingAs($creator)->post(route('collections.savenew'), [
            'name'            => 'ZzTestSeasonWithoutSeasons',
            'published_state' => PublishedState::WORLD,
            'season_id'       => $season->id,
        ]);

        // Assert
        $response->assertSessionHasErrors(['season_id' => __('validation.custom.collection_season_id.no_seasons')]);
        $this->assertSame(0, DungeonRouteCollection::query()->where('user_id', $creator->id)->count());
    }

    #[Test]
    public function savenew_givenASeasonOfAnotherExpansion_failsValidation(): void
    {
        // Arrange
        $creator = $this->creator();
        $season  = $this->createSeason(['expansion_id' => $this->gameVersionWithoutSeasons()->expansion_id]);

        // Act
        $response = $this->actingAs($creator)->post(route('collections.savenew'), [
            'name'            => 'ZzTestSeasonOfAnotherExpansion',
            'published_state' => PublishedState::WORLD,
            'season_id'       => $season->id,
        ]);

        // Assert
        $response->assertSessionHasErrors(['season_id' => __('validation.custom.collection_season_id.expansion')]);
        $this->assertSame(0, DungeonRouteCollection::query()->where('user_id', $creator->id)->count());
    }

    #[Test]
    public function savenew_givenASeasonThatDoesNotExist_failsValidation(): void
    {
        // Arrange
        $creator = $this->creator();

        // Act
        $response = $this->actingAs($creator)->post(route('collections.savenew'), [
            'name'            => 'ZzTestMissingSeason',
            'published_state' => PublishedState::WORLD,
            'season_id'       => (int)Season::query()->max('id') + 1000,
        ]);

        // Assert
        $response->assertSessionHasErrors('season_id');
    }

    #[Test]
    public function savenew_givenARouteOfAnotherGameVersion_failsValidation(): void
    {
        // Arrange
        $creator      = $this->creator();
        $dungeonRoute = $this->createRoute($this->nonRetailMappingVersion());

        // Act
        $response = $this->actingAs($creator)->post(route('collections.savenew'), [
            'name'            => 'ZzTestWrongGameVersion',
            'published_state' => PublishedState::WORLD,
            'dungeon_routes'  => [$dungeonRoute->id],
        ]);

        // Assert
        $response->assertSessionHasErrors(['dungeon_routes.0' => __('validation.custom.collection_dungeon_routes.game_version')]);
        $this->assertSame(0, DungeonRouteCollection::query()->where('user_id', $creator->id)->count());
    }

    #[Test]
    public function savenew_givenARouteWithoutAMappingVersion_failsValidation(): void
    {
        // Arrange
        $creator      = $this->creator();
        $dungeonRoute = $this->createRoute($this->retailMappingVersions()->first());
        DungeonRoute::query()->whereKey($dungeonRoute->id)->update(['mapping_version_id' => null]);

        // Act
        $response = $this->actingAs($creator)->post(route('collections.savenew'), [
            'name'            => 'ZzTestNoMappingVersion',
            'published_state' => PublishedState::WORLD,
            'dungeon_routes'  => [$dungeonRoute->id],
        ]);

        // Assert
        $response->assertSessionHasErrors(['dungeon_routes.0' => __('validation.custom.collection_dungeon_routes.game_version')]);
    }

    #[Test]
    public function savenew_givenARouteOfAnotherSeasonForASeasonSet_failsValidation(): void
    {
        // Arrange
        $creator       = $this->creator();
        $currentSeason = $this->currentRetailSeason();
        $otherSeason   = $this->createSeason(['expansion_id' => $this->retail()->expansion_id]);
        $dungeonRoute  = $this->createRoute($this->retailMappingVersions()->first(), $otherSeason);

        // Act
        $response = $this->actingAs($creator)->post(route('collections.savenew'), [
            'name'            => 'ZzTestWrongSeason',
            'published_state' => PublishedState::WORLD,
            'season_id'       => $currentSeason->id,
            'dungeon_routes'  => [$dungeonRoute->id],
        ]);

        // Assert
        $response->assertSessionHasErrors(['dungeon_routes.0' => __('validation.custom.collection_dungeon_routes.season')]);
        $this->assertSame(0, DungeonRouteCollection::query()->where('user_id', $creator->id)->count());
    }

    #[Test]
    public function update_givenARouteOfTheSeason_addsItToTheSeasonSet(): void
    {
        // Arrange
        $creator                = $this->creator();
        $season                 = $this->createSeason(['expansion_id' => $this->retail()->expansion_id]);
        $dungeonRoute           = $this->createRoute($this->retailMappingVersions()->first(), $season);
        $dungeonRouteCollection = $this->createCollection(DungeonRouteCollection::factory()->seasonSet($season));

        // Act
        $response = $this->actingAs($creator)->patch($this->updateUrl($dungeonRouteCollection), [
            'name'            => 'ZzTestSeasonSet',
            'published_state' => PublishedState::WORLD,
            'dungeon_routes'  => [$dungeonRoute->id],
        ]);

        // Assert
        $response->assertSessionHasNoErrors();
        $this->assertSame([$dungeonRoute->id], $dungeonRouteCollection->refresh()->dungeonRoutes->pluck('id')->all());
        $this->assertSame($season->id, $dungeonRouteCollection->season_id, 'Not posting a season keeps the season');
    }

    #[Test]
    public function update_givenARouteOfAnotherSeason_failsValidation(): void
    {
        // Arrange
        $creator                = $this->creator();
        $season                 = $this->createSeason(['expansion_id' => $this->retail()->expansion_id]);
        $dungeonRoute           = $this->createRoute($this->retailMappingVersions()->first());
        $dungeonRouteCollection = $this->createCollection(DungeonRouteCollection::factory()->seasonSet($season));

        // Act
        $response = $this->actingAs($creator)->patch($this->updateUrl($dungeonRouteCollection), [
            'name'            => 'ZzTestSeasonSet',
            'published_state' => PublishedState::WORLD,
            'dungeon_routes'  => [$dungeonRoute->id],
        ]);

        // Assert
        $response->assertSessionHasErrors(['dungeon_routes.0' => __('validation.custom.collection_dungeon_routes.season')]);
        $this->assertSame(0, $dungeonRouteCollection->dungeonRouteCollectionRoutes()->count());
    }

    #[Test]
    public function update_givenARouteOfAnotherGameVersion_failsValidation(): void
    {
        // Arrange
        $creator                = $this->creator();
        $dungeonRoute           = $this->createRoute($this->nonRetailMappingVersion());
        $dungeonRouteCollection = $this->createCollection(DungeonRouteCollection::factory()->freeForm($this->retail()));

        // Act
        $response = $this->actingAs($creator)->patch($this->updateUrl($dungeonRouteCollection), [
            'name'            => 'ZzTestFreeForm',
            'published_state' => PublishedState::WORLD,
            'dungeon_routes'  => [$dungeonRoute->id],
        ]);

        // Assert
        $response->assertSessionHasErrors(['dungeon_routes.0' => __('validation.custom.collection_dungeon_routes.game_version')]);
    }

    #[Test]
    public function update_givenADifferentSeason_failsValidation(): void
    {
        // Arrange
        $creator                = $this->creator();
        $season                 = $this->createSeason(['expansion_id' => $this->retail()->expansion_id]);
        $dungeonRouteCollection = $this->createCollection(DungeonRouteCollection::factory()->seasonSet($season));

        // Act
        $response = $this->actingAs($creator)->patch($this->updateUrl($dungeonRouteCollection), [
            'name'            => 'ZzTestSeasonSet',
            'published_state' => PublishedState::WORLD,
            'season_id'       => $this->currentRetailSeason()->id,
        ]);

        // Assert
        $response->assertSessionHasErrors(['season_id' => __('validation.custom.collection_season_id.fixed')]);
        $this->assertSame($season->id, $dungeonRouteCollection->refresh()->season_id);
    }

    #[Test]
    public function update_givenASeasonForAFreeFormCollection_failsValidation(): void
    {
        // Arrange
        $creator                = $this->creator();
        $dungeonRouteCollection = $this->createCollection(DungeonRouteCollection::factory()->freeForm($this->retail()));

        // Act
        $response = $this->actingAs($creator)->patch($this->updateUrl($dungeonRouteCollection), [
            'name'            => 'ZzTestFreeForm',
            'published_state' => PublishedState::WORLD,
            'season_id'       => $this->currentRetailSeason()->id,
        ]);

        // Assert
        $response->assertSessionHasErrors(['season_id' => __('validation.custom.collection_season_id.fixed')]);
        $this->assertNull($dungeonRouteCollection->refresh()->season_id);
    }

    #[Test]
    public function update_givenTheSameSeason_keepsTheSeasonSet(): void
    {
        // Arrange
        $creator                = $this->creator();
        $season                 = $this->createSeason(['expansion_id' => $this->retail()->expansion_id]);
        $dungeonRouteCollection = $this->createCollection(DungeonRouteCollection::factory()->seasonSet($season));

        // Act
        $response = $this->actingAs($creator)->patch($this->updateUrl($dungeonRouteCollection), [
            'name'            => 'ZzTestSeasonSet',
            'published_state' => PublishedState::WORLD,
            'season_id'       => $season->id,
        ]);

        // Assert
        $response->assertSessionHasNoErrors();
        $this->assertSame($season->id, $dungeonRouteCollection->refresh()->season_id);
    }

    #[Test]
    public function update_givenAnEmptySeason_makesTheSeasonSetFreeForm(): void
    {
        // Arrange
        $creator                = $this->creator();
        $season                 = $this->createSeason(['expansion_id' => $this->retail()->expansion_id]);
        $dungeonRoute           = $this->createRoute($this->retailMappingVersions()->first(), $season);
        $dungeonRouteCollection = $this->createCollection(DungeonRouteCollection::factory()->seasonSet($season));
        $this->addRoutes($dungeonRouteCollection, [$dungeonRoute]);

        // Act
        $response = $this->actingAs($creator)->patch($this->updateUrl($dungeonRouteCollection), [
            'name'            => 'ZzTestNowFreeForm',
            'published_state' => PublishedState::WORLD,
            'season_id'       => '',
            'dungeon_routes'  => [$dungeonRoute->id],
        ]);

        // Assert
        $response->assertSessionHasNoErrors();
        $dungeonRouteCollection->refresh();
        $this->assertNull($dungeonRouteCollection->season_id);
        $this->assertSame($this->retail()->id, $dungeonRouteCollection->game_version_id);
        $this->assertSame([$dungeonRoute->id], $dungeonRouteCollection->dungeonRoutes->pluck('id')->all());
    }

    #[Test]
    public function update_givenAPostedGameVersion_ignoresIt(): void
    {
        // Arrange
        $creator                = $this->creator();
        $season                 = $this->createSeason(['expansion_id' => $this->retail()->expansion_id]);
        $dungeonRouteCollection = $this->createCollection(DungeonRouteCollection::factory()->seasonSet($season));

        // Act
        $response = $this->actingAs($creator)->patch($this->updateUrl($dungeonRouteCollection), [
            'name'            => 'ZzTestSeasonSet',
            'published_state' => PublishedState::WORLD,
            'game_version_id' => $this->gameVersionWithoutSeasons()->id,
        ]);

        // Assert
        $response->assertSessionHasNoErrors();
        $dungeonRouteCollection->refresh();
        $this->assertSame($this->retail()->id, $dungeonRouteCollection->game_version_id);
        $this->assertSame($season->id, $dungeonRouteCollection->season_id);
    }

    #[Test]
    public function update_givenASeasonSetWhoseSeasonIsNoLongerOfTheGameVersionsExpansion_savesIt(): void
    {
        // Arrange
        $creator                = $this->creator();
        $mappingVersion         = $this->retailMappingVersions()->first();
        $season                 = $this->createSeason(['expansion_id' => $this->gameVersionWithoutSeasons()->expansion_id], [$mappingVersion->dungeon_id]);
        $dungeonRoute           = $this->createRoute($mappingVersion, $season);
        $dungeonRouteCollection = $this->createCollection(DungeonRouteCollection::factory()->seasonSet($season));

        // Act
        $response = $this->actingAs($creator)->patch($this->updateUrl($dungeonRouteCollection), [
            'name'            => 'ZzTestRenamedAfterRollover',
            'published_state' => PublishedState::WORLD_WITH_LINK,
            'season_id'       => $season->id,
            'dungeon_routes'  => [$dungeonRoute->id],
        ]);

        // Assert
        $response->assertSessionHasNoErrors();
        $dungeonRouteCollection->refresh();
        $this->assertSame('ZzTestRenamedAfterRollover', $dungeonRouteCollection->name);
        $this->assertSame(PublishedState::ALL[PublishedState::WORLD_WITH_LINK], $dungeonRouteCollection->published_state_id);
        $this->assertSame($season->id, $dungeonRouteCollection->season_id);
        $this->assertSame([$dungeonRoute->id], $dungeonRouteCollection->dungeonRoutes->pluck('id')->all());
    }

    #[Test]
    public function update_givenACollectionOnAnInactiveGameVersion_savesItWithoutChangingTheGameVersion(): void
    {
        // Arrange
        $creator                = $this->creator();
        $gameVersion            = $this->inactiveGameVersion();
        $dungeonRouteCollection = $this->createCollection(DungeonRouteCollection::factory()->freeForm($gameVersion));

        // Act
        $response = $this->actingAs($creator)->patch($this->updateUrl($dungeonRouteCollection), [
            'name'            => 'ZzTestInactiveGameVersion',
            'published_state' => PublishedState::WORLD,
        ]);

        // Assert
        $response->assertSessionHasNoErrors();
        $this->assertSame($gameVersion->id, $dungeonRouteCollection->refresh()->game_version_id);
    }

    #[Test]
    public function index_givenACollectionOnAnInactiveGameVersion_keepsItReachable(): void
    {
        // Arrange
        $creator                = $this->creator();
        $gameVersion            = $this->inactiveGameVersion();
        $dungeonRouteCollection = $this->createCollection(DungeonRouteCollection::factory()->freeForm($gameVersion));

        // Act
        $overviewResponse = $this->actingAs($creator)->get(route('collections.index'));
        $filteredResponse = $this->actingAs($creator)->get(route('collections.index', ['game_version_id' => $gameVersion->id]));

        // Assert
        $overviewResponse->assertOk();
        $this->assertContains($gameVersion->id, $overviewResponse->viewData('gameVersions')->pluck('id')->all(), 'The filter offers the inactive game version');
        $filteredResponse->assertSessionHasNoErrors();
        $this->assertSame([$dungeonRouteCollection->id], $filteredResponse->viewData('dungeonRouteCollections')->pluck('id')->all());
    }

    #[Test]
    public function index_givenAnInactiveGameVersionTheUserHasNoCollectionOn_failsValidation(): void
    {
        // Arrange
        $creator = $this->creator();

        // Act
        $response = $this->actingAs($creator)->get(route('collections.index', ['game_version_id' => $this->inactiveGameVersion()->id]));

        // Assert
        $response->assertSessionHasErrors('game_version_id');
    }

    #[Test]
    public function edit_givenARouteWhoseMappingVersionRequiresNoEnemyForces_showsNoEnemyForces(): void
    {
        // Arrange
        $creator                = $this->creator();
        $mappingVersion         = MappingVersion::query()->where('enemy_forces_required', 0)->orderByDesc('id')->firstOrFail();
        $dungeonRoute           = $this->createRoute($mappingVersion, null, 'ZzTestNoEnemyForces');
        $dungeonRouteCollection = $this->createCollection(DungeonRouteCollection::factory()->freeForm(GameVersion::query()->findOrFail($mappingVersion->game_version_id)));
        $this->addRoutes($dungeonRouteCollection, [$dungeonRoute]);

        // Act
        $response = $this->actingAs($creator)->get(route('collections.edit', ['dungeonRouteCollection' => $dungeonRouteCollection]));

        // Assert
        $response->assertOk();
        $this->assertMatchesRegularExpression(
            sprintf('/data-id="%d".*?<span class="ordered_select_detail"\s+hidden/s', $dungeonRoute->id),
            (string)$response->getContent(),
        );
    }

    #[Test]
    public function create_givenAGameVersionAndSeasonQuery_opensWithThemAndOffersThatSeasonsRoutes(): void
    {
        // Arrange
        $creator = $this->creator();
        $creator->update(['game_version_id' => $this->retail()->id]);
        $mappingVersion = $this->retailMappingVersions()->first();
        $season         = $this->createSeason(['expansion_id' => $this->retail()->expansion_id, 'active' => true], [$mappingVersion->dungeon_id]);
        $this->createRoute($mappingVersion, $season, 'ZzTestOfTheRequestedSeason');
        $this->createRoute($mappingVersion, null, 'ZzTestOfNoSeason');

        // Act
        $response = $this->actingAs($creator)->get(route('collections.new', [
            'season_id' => $season->id,
        ]));

        // Assert
        $response->assertOk();
        $this->assertSame($season->id, $response->viewData('selectedSeason')?->id);
        $content = (string)$response->getContent();
        $this->assertStringContainsString(sprintf('id="dungeon_routes_%d"', $mappingVersion->dungeon_id), $content);
        $this->assertStringContainsString('ZzTestOfTheRequestedSeason', $content);
        $this->assertStringNotContainsString('ZzTestOfNoSeason', $content);
    }

    #[Test]
    public function create_givenTheNoSeasonQuery_opensFreeForm(): void
    {
        // Arrange
        $creator = $this->creator();
        $creator->update(['game_version_id' => $this->retail()->id]);

        // Act
        $response = $this->actingAs($creator)->get(route('collections.new', [
            'season_id' => DungeonRouteCollectionCreateFormRequest::SEASON_NONE,
        ]));

        // Assert
        $response->assertOk();
        $this->assertNull($response->viewData('selectedSeason'));
        $this->assertMatchesRegularExpression(
            '/<input type="radio" name="season_id" id="season_id_none"[^>]*checked/',
            (string)$response->getContent(),
        );
    }

    #[Test]
    public function create_givenAUserOnAGameVersionWithoutSeasons_opensItWithoutASeasonField(): void
    {
        // Arrange
        $creator     = $this->creator();
        $gameVersion = $this->gameVersionWithoutSeasons();
        $creator->update(['game_version_id' => $gameVersion->id]);

        // Act
        $response = $this->actingAs($creator)->get(route('collections.new'));

        // Assert
        $response->assertOk();
        $this->assertSame($gameVersion->id, $response->viewData('selectedGameVersion')->id);
        $this->assertNull($response->viewData('selectedSeason'));
        $response->assertDontSee('name="season_id"', false);
        $response->assertSeeText(__($gameVersion->name));
    }

    #[Test]
    public function create_givenAGameVersionQuery_ignoresIt(): void
    {
        // Arrange
        $creator = $this->creator();

        // Act
        $response = $this->actingAs($creator)->get(route('collections.new', ['game_version_id' => $this->gameVersionWithoutSeasons()->id]));

        // Assert
        $response->assertOk();
        $this->assertSame($this->retail()->id, $response->viewData('selectedGameVersion')->id);
    }

    #[Test]
    #[DataProvider('invalidCreateQueryProvider')]
    public function create_givenAnInvalidQuery_failsValidation(string $case, string $errorKey): void
    {
        // Arrange
        $creator = $this->creator();
        if ($case === 'season_on_no_seasons') {
            $creator->update(['game_version_id' => $this->gameVersionWithoutSeasons()->id]);
        }
        $query = match ($case) {
            'unknown_season'            => ['season_id' => (int)Season::query()->max('id') + 1000],
            'season_not_a_number'       => ['season_id' => 'current'],
            'season_on_no_seasons'      => ['season_id' => $this->currentRetailSeason()->id],
            'season_of_other_expansion' => ['season_id' => $this->createSeason(['expansion_id' => $this->gameVersionWithoutSeasons()->expansion_id])->id],
            default                     => throw new \InvalidArgumentException($case),
        };

        // Act
        $response = $this->actingAs($creator)->getJson(route('collections.new', $query));

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors($errorKey);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function invalidCreateQueryProvider(): array
    {
        return [
            'unknown season'              => ['unknown_season', 'season_id'],
            'season not a number'         => ['season_not_a_number', 'season_id'],
            'season on a version without' => ['season_on_no_seasons', 'season_id'],
            'season of another expansion' => ['season_of_other_expansion', 'season_id'],
        ];
    }

    #[Test]
    public function edit_givenASeasonSet_countsEachSlotAndTheCollectionAsAWhole(): void
    {
        // Arrange
        $creator                = $this->creator();
        $mappingVersions        = $this->retailMappingVersions()->take(2)->values();
        $season                 = $this->createSeason(['expansion_id' => $this->retail()->expansion_id], $mappingVersions->pluck('dungeon_id')->all());
        $first                  = $this->createRoute($mappingVersions->get(0), $season);
        $second                 = $this->createRoute($mappingVersions->get(0), $season);
        $dungeonRouteCollection = $this->createCollection(DungeonRouteCollection::factory()->seasonSet($season));
        $this->addRoutes($dungeonRouteCollection, [$first, $second]);

        // Act
        $response = $this->actingAs($creator)->get(route('collections.edit', ['dungeonRouteCollection' => $dungeonRouteCollection]));

        // Assert
        $response->assertOk();
        $content = (string)$response->getContent();
        $this->assertMatchesRegularExpression(sprintf('/id="dungeon_routes_%d_count"[^>]*>\s*2\s*</', $mappingVersions->get(0)->dungeon_id), $content);
        $this->assertMatchesRegularExpression(sprintf('/id="dungeon_routes_%d_count"[^>]*>\s*0\s*</', $mappingVersions->get(1)->dungeon_id), $content);
        $this->assertMatchesRegularExpression(
            sprintf('/id="collection_dungeon_routes_total"[^>]*>\s*%s\s*</', preg_quote(__('view_common.forms.orderedselect.count', ['count' => 2, 'max' => DungeonRouteCollection::MAX_ROUTES]), '/')),
            $content,
        );
    }

    #[Test]
    public function view_givenASeasonSet_showsOneSlotPerPoolDungeonInPoolOrderWithGaps(): void
    {
        // Arrange
        $this->creator();
        $mappingVersions        = $this->retailMappingVersions()->take(3)->values();
        $poolDungeonIds         = $mappingVersions->pluck('dungeon_id')->all();
        $season                 = $this->createSeason(['expansion_id' => $this->retail()->expansion_id], $poolDungeonIds);
        $secondSlotFirst        = $this->createRoute($mappingVersions->get(1), $season, 'ZzTestSecondSlotFirst');
        $thirdSlot              = $this->createRoute($mappingVersions->get(2), $season, 'ZzTestThirdSlot');
        $secondSlotSecond       = $this->createRoute($mappingVersions->get(1), $season, 'ZzTestSecondSlotSecond');
        $dungeonRouteCollection = $this->createCollection(DungeonRouteCollection::factory()->seasonSet($season));
        $this->addRoutes($dungeonRouteCollection, [$thirdSlot, $secondSlotFirst, $secondSlotSecond]);

        // Act
        $response = $this->get(route('collection.view', ['dungeonRouteCollection' => $dungeonRouteCollection]));

        // Assert
        $response->assertOk();
        /** @var Collection<int, DungeonRouteCollectionGroup> $groups */
        $groups = $response->viewData('dungeonRouteGroups');
        $this->assertSame($poolDungeonIds, $groups->map(static fn(DungeonRouteCollectionGroup $group): ?int => $group->dungeon?->id)->all());
        $this->assertSame([], $groups->get(0)->dungeonRoutes->pluck('id')->all(), 'An empty slot is still shown');
        $this->assertSame([$secondSlotFirst->id, $secondSlotSecond->id], $groups->get(1)->dungeonRoutes->pluck('id')->all());
        $this->assertSame([$thirdSlot->id], $groups->get(2)->dungeonRoutes->pluck('id')->all());
        $response->assertSeeText(__('view_collection.view.slot_empty', ['dungeon' => __($groups->get(0)->dungeon->name)]));
        $response->assertSeeText(__('view_collection.kind.season_set', ['season' => $season->name, 'covered' => 2, 'total' => 3]));
    }

    #[Test]
    public function view_givenAFreeFormCollection_groupsPerDungeonInOrderOfFirstAppearance(): void
    {
        // Arrange
        $this->creator();
        $mappingVersions        = $this->retailMappingVersions()->take(2)->values();
        $firstDungeonA          = $this->createRoute($mappingVersions->get(1), null, 'ZzTestFirstDungeonA');
        $secondDungeon          = $this->createRoute($mappingVersions->get(0), null, 'ZzTestSecondDungeon');
        $firstDungeonB          = $this->createRoute($mappingVersions->get(1), null, 'ZzTestFirstDungeonB');
        $dungeonRouteCollection = $this->createCollection(DungeonRouteCollection::factory()->freeForm($this->retail()));
        $this->addRoutes($dungeonRouteCollection, [$firstDungeonA, $secondDungeon, $firstDungeonB]);

        // Act
        $response = $this->get(route('collection.view', ['dungeonRouteCollection' => $dungeonRouteCollection]));

        // Assert
        $response->assertOk();
        /** @var Collection<int, DungeonRouteCollectionGroup> $groups */
        $groups = $response->viewData('dungeonRouteGroups');
        $this->assertSame(
            [$mappingVersions->get(1)->dungeon_id, $mappingVersions->get(0)->dungeon_id],
            $groups->map(static fn(DungeonRouteCollectionGroup $group): ?int => $group->dungeon?->id)->all(),
        );
        $this->assertSame([$firstDungeonA->id, $firstDungeonB->id], $groups->get(0)->dungeonRoutes->pluck('id')->all());
        $this->assertSame([$secondDungeon->id], $groups->get(1)->dungeonRoutes->pluck('id')->all());
        $response->assertSeeText(trans_choice('view_collection.kind.free_form', 2, ['game_version' => __($this->retail()->name), 'count' => 2]));
    }

    #[Test]
    public function view_givenAnEmptyFreeFormCollection_showsTheEmptyStateInsteadOfDungeons(): void
    {
        // Arrange
        $this->creator();
        $dungeonRouteCollection = $this->createCollection(DungeonRouteCollection::factory()->freeForm($this->retail()));

        // Act
        $response = $this->get(route('collection.view', ['dungeonRouteCollection' => $dungeonRouteCollection]));

        // Assert
        $response->assertOk();
        $response->assertSeeText(__('view_collection.view.no_routes'));
    }

    #[Test]
    public function edit_givenASeasonSet_rendersOneOrderedListPerPoolDungeon(): void
    {
        // Arrange
        $creator         = $this->creator();
        $mappingVersions = $this->retailMappingVersions()->take(2)->values();
        $season          = $this->createSeason(['expansion_id' => $this->retail()->expansion_id], $mappingVersions->pluck('dungeon_id')->all());
        $inSlot          = $this->createRoute($mappingVersions->get(1), $season, 'ZzTestInSlot');
        $this->createRoute($mappingVersions->get(0), $season, 'ZzTestOffered');
        $this->createRoute($mappingVersions->get(0), null, 'ZzTestNotOfTheSeason');
        $dungeonRouteCollection = $this->createCollection(DungeonRouteCollection::factory()->seasonSet($season));
        $this->addRoutes($dungeonRouteCollection, [$inSlot]);

        // Act
        $response = $this->actingAs($creator)->get(route('collections.edit', ['dungeonRouteCollection' => $dungeonRouteCollection]));

        // Assert
        $response->assertOk();
        $content = (string)$response->getContent();
        foreach ($mappingVersions as $mappingVersion) {
            $this->assertStringContainsString(sprintf('id="dungeon_routes_%d"', $mappingVersion->dungeon_id), $content);
        }
        $this->assertStringContainsString('ZzTestOffered', $content);
        $this->assertStringNotContainsString('ZzTestNotOfTheSeason', $content, 'A route of another season is not offered');
    }

    #[Test]
    public function create_givenARetailUser_offersTheCurrentSeasonByDefault(): void
    {
        // Arrange
        $creator       = $this->creator();
        $currentSeason = $this->currentRetailSeason();
        $creator->update(['game_version_id' => $this->retail()->id]);

        // Act
        $response = $this->actingAs($creator)->get(route('collections.new'));

        // Assert
        $response->assertOk();
        $this->assertSame($currentSeason->id, $response->viewData('selectedSeason')?->id);
        $this->assertMatchesRegularExpression(
            sprintf('/<input type="radio" name="season_id" id="season_id_%d"[^>]*value="%d"[^>]*checked/', $currentSeason->id, $currentSeason->id),
            (string)$response->getContent(),
        );
    }

    #[Test]
    public function create_givenAGameVersionWithSeasons_offersEachSeasonWithItsExpansionIconAndFreeForm(): void
    {
        // Arrange
        $creator = $this->creator();
        $creator->update(['game_version_id' => $this->retail()->id]);
        $season = $this->currentRetailSeason();

        // Act
        $response = $this->actingAs($creator)->get(route('collections.new'));

        // Assert
        $response->assertOk();
        $content = (string)$response->getContent();
        $this->assertMatchesRegularExpression(
            sprintf(
                '/<label class="btn btn-secondary" for="season_id_%d">.*?<img src="%s" alt="%s" class="collection_season_icon">\\s*%s\\s*<\/label>/s',
                $season->id,
                preg_quote($season->expansion->getIconUrl(), '/'),
                preg_quote(e(__($season->expansion->name)), '/'),
                preg_quote(e($season->name), '/'),
            ),
            $content,
        );
        $this->assertStringNotContainsString(e($season->name_long), $content, 'The label leaves the expansion to the icon');
        $this->assertMatchesRegularExpression('/<input type="radio" name="season_id" id="season_id_none"[^>]*value=""/', $content);
        $this->assertStringNotContainsString('name="game_version_id"', $content);
    }

    #[Test]
    public function edit_givenRoutes_showsEachRoutesEnemyForcesAgainstTheRequirement(): void
    {
        // Arrange
        $creator        = $this->creator();
        $mappingVersion = $this->retailMappingVersions()->first(static fn(MappingVersion $mappingVersion): bool => $mappingVersion->enemy_forces_required > 1);
        $season         = $this->createSeason(['expansion_id' => $this->retail()->expansion_id], [$mappingVersion->dungeon_id]);
        $enough         = $this->createRoute($mappingVersion, $season, 'ZzTestEnough');
        $short          = $this->createRoute($mappingVersion, $season, 'ZzTestShort');
        $offered        = $this->createRoute($mappingVersion, $season, 'ZzTestOffered');
        $required       = $mappingVersion->enemy_forces_required;
        DungeonRoute::query()->whereKey($enough->id)->update(['enemy_forces' => $required + 2]);
        DungeonRoute::query()->whereKey($short->id)->update(['enemy_forces' => $required - 1]);
        DungeonRoute::query()->whereKey($offered->id)->update(['enemy_forces' => $required - 1]);
        $dungeonRouteCollection = $this->createCollection(DungeonRouteCollection::factory()->seasonSet($season));
        $this->addRoutes($dungeonRouteCollection, [$enough, $short]);

        // Act
        $response = $this->actingAs($creator)->get(route('collections.edit', ['dungeonRouteCollection' => $dungeonRouteCollection]));

        // Assert
        $response->assertOk();
        $content = (string)$response->getContent();
        $this->assertMatchesRegularExpression(
            sprintf('/data-id="%d".*?<span class="ordered_select_detail"\s[^>]*>.*?%s/s', $enough->id, preg_quote(sprintf('%d / %d', $required + 2, $required), '/')),
            $content,
        );
        $this->assertMatchesRegularExpression(
            sprintf('/data-id="%d".*?<span class="ordered_select_detail ordered_select_detail_warning"[^>]*>.*?%s/s', $short->id, preg_quote(sprintf('%d / %d', $required - 1, $required), '/')),
            $content,
        );
        $this->assertMatchesRegularExpression(
            sprintf('/<option value="%d"[^>]*data-detail="%s" data-detail-warning="1"/', $offered->id, preg_quote(sprintf('%d / %d', $required - 1, $required), '/')),
            $content,
        );
    }

    #[Test]
    public function index_givenNoFilter_listsTheUsersGameVersionOnly(): void
    {
        // Arrange
        $creator = $this->creator();
        $creator->update(['game_version_id' => $this->retail()->id]);
        $retailCollection = $this->createCollection(DungeonRouteCollection::factory()->freeForm($this->retail()));
        $this->createCollection(DungeonRouteCollection::factory()->freeForm($this->gameVersionWithoutSeasons()));

        // Act
        $response = $this->actingAs($creator)->get(route('collections.index'));

        // Assert
        $response->assertOk();
        $this->assertSame([$retailCollection->id], $response->viewData('dungeonRouteCollections')->pluck('id')->all());
    }

    #[Test]
    public function index_givenAGameVersionWithoutSeasons_listsItsCollectionsWithoutASeasonFilter(): void
    {
        // Arrange
        $creator         = $this->creator();
        $gameVersion     = $this->gameVersionWithoutSeasons();
        $otherCollection = $this->createCollection(DungeonRouteCollection::factory()->freeForm($gameVersion));
        $this->createCollection(DungeonRouteCollection::factory()->freeForm($this->retail()));

        // Act
        $response = $this->actingAs($creator)->get(route('collections.index', ['game_version_id' => $gameVersion->id]));

        // Assert
        $response->assertOk();
        $this->assertSame([$otherCollection->id], $response->viewData('dungeonRouteCollections')->pluck('id')->all());
        $response->assertDontSee('name="season"', false);
    }

    #[Test]
    public function index_givenTheNoSeasonFilter_listsOnlyFreeFormCollections(): void
    {
        // Arrange
        $creator  = $this->creator();
        $freeForm = $this->createCollection(DungeonRouteCollection::factory()->freeForm($this->retail()));
        $this->createCollection(DungeonRouteCollection::factory()->seasonSet($this->currentRetailSeason()));

        // Act
        $response = $this->actingAs($creator)->get(route('collections.index', [
            'game_version_id' => $this->retail()->id,
            'season'          => DungeonRouteCollectionIndexFormRequest::SEASON_NONE,
        ]));

        // Assert
        $response->assertOk();
        $this->assertSame([$freeForm->id], $response->viewData('dungeonRouteCollections')->pluck('id')->all());
    }

    #[Test]
    public function index_givenASeasonFilter_listsOnlyThatSeasonsSets(): void
    {
        // Arrange
        $creator   = $this->creator();
        $seasonSet = $this->createCollection(DungeonRouteCollection::factory()->seasonSet($this->currentRetailSeason()));
        $this->createCollection(DungeonRouteCollection::factory()->freeForm($this->retail()));

        // Act
        $response = $this->actingAs($creator)->get(route('collections.index', [
            'game_version_id' => $this->retail()->id,
            'season'          => $this->currentRetailSeason()->id,
        ]));

        // Assert
        $response->assertOk();
        $this->assertSame([$seasonSet->id], $response->viewData('dungeonRouteCollections')->pluck('id')->all());
    }

    #[Test]
    public function index_givenAnInvalidSeasonFilter_failsValidation(): void
    {
        // Arrange
        $creator = $this->creator();

        // Act
        $response = $this->actingAs($creator)->get(route('collections.index', ['season' => 'not-a-season']));

        // Assert
        $response->assertSessionHasErrors('season');
    }

    #[Test]
    public function index_givenEveryKind_sortsCurrentSeasonSetsThenFreeFormThenOlderSeasons(): void
    {
        // Arrange
        $creator     = $this->creator();
        $olderSeason = $this->createSeason(['expansion_id' => $this->retail()->expansion_id]);
        $olderSet    = $this->createCollection(DungeonRouteCollection::factory()->seasonSet($olderSeason), Carbon::now());
        $freeFormOld = $this->createCollection(DungeonRouteCollection::factory()->freeForm($this->retail()), Carbon::now()->subDays(3));
        $freeFormNew = $this->createCollection(DungeonRouteCollection::factory()->freeForm($this->retail()), Carbon::now()->subDay());
        $currentSet  = $this->createCollection(DungeonRouteCollection::factory()->seasonSet($this->currentRetailSeason()), Carbon::now()->subDays(5));

        // Act
        $response = $this->actingAs($creator)->get(route('collections.index', ['game_version_id' => $this->retail()->id]));

        // Assert
        $response->assertOk();
        $this->assertSame(
            [$currentSet->id, $freeFormNew->id, $freeFormOld->id, $olderSet->id],
            $response->viewData('dungeonRouteCollections')->pluck('id')->all(),
        );
    }

    #[Test]
    public function index_givenCollections_labelsEachWithItsKind(): void
    {
        // Arrange
        $creator                = $this->creator();
        $mappingVersions        = $this->retailMappingVersions()->take(2)->values();
        $season                 = $this->createSeason(['expansion_id' => $this->retail()->expansion_id], $mappingVersions->pluck('dungeon_id')->all());
        $dungeonRoute           = $this->createRoute($mappingVersions->get(0), $season);
        $dungeonRouteCollection = $this->createCollection(DungeonRouteCollection::factory()->seasonSet($season));
        $this->addRoutes($dungeonRouteCollection, [$dungeonRoute]);

        // Act
        $response = $this->actingAs($creator)->get(route('collections.index', [
            'game_version_id' => $this->retail()->id,
            'season'          => $season->id,
        ]));

        // Assert
        $response->assertOk();
        $response->assertSeeText(__('view_collection.kind.season_set', ['season' => $season->name, 'covered' => 1, 'total' => 2]));
    }

    private function creator(): User
    {
        if ($this->creator === null) {
            $this->creator = User::factory()->create(['game_version_id' => GameVersion::ALL[GameVersion::GAME_VERSION_RETAIL]]);
            $this->creator->addRole(Role::ROLE_USER);
            Feature::for($this->creator)->activate(CreatorProfiles::class);
            Feature::for(null)->activate(CreatorProfiles::class);
            $this->beforeApplicationDestroyed(static fn() => Feature::for(null)->forget(CreatorProfiles::class));
        }

        return $this->creator;
    }

    private function retail(): GameVersion
    {
        return GameVersion::query()->findOrFail(GameVersion::ALL[GameVersion::GAME_VERSION_RETAIL]);
    }

    private function gameVersionWithoutSeasons(): GameVersion
    {
        return GameVersion::query()->where('has_seasons', false)->where('active', true)->orderBy('id')->firstOrFail();
    }

    private function inactiveGameVersion(): GameVersion
    {
        return GameVersion::query()->findOrFail(GameVersion::ALL[GameVersion::GAME_VERSION_BETA]);
    }

    private function currentRetailSeason(): Season
    {
        $season = app(SeasonServiceInterface::class)->getCurrentSeason($this->retail()->expansion);
        $this->assertNotNull($season, 'Retail must have a current season (seed the database).');

        return $season;
    }

    /**
     * Retail mapping versions of distinct challenge mode dungeons, newest first.
     *
     * @return Collection<int, MappingVersion>
     */
    private function retailMappingVersions(): Collection
    {
        return MappingVersion::query()
            ->where('game_version_id', $this->retail()->id)
            ->whereHas('dungeon', static fn(Builder $query) => $query->whereNotNull('challenge_mode_id'))
            ->orderByDesc('id')
            ->get()
            ->unique('dungeon_id')
            ->values();
    }

    private function nonRetailMappingVersion(): MappingVersion
    {
        return MappingVersion::query()
            ->where('game_version_id', '!=', $this->retail()->id)
            ->orderByDesc('id')
            ->firstOrFail();
    }

    private function createRoute(MappingVersion $mappingVersion, ?Season $season = null, ?string $title = null): DungeonRoute
    {
        $attributes = [
            'author_id'          => $this->creator()->id,
            'dungeon_id'         => $mappingVersion->dungeon_id,
            'mapping_version_id' => $mappingVersion->id,
            'season_id'          => $season?->id,
            'expires_at'         => null,
            'published_state_id' => PublishedState::ALL[PublishedState::WORLD],
        ];

        if ($title !== null) {
            $attributes['title'] = $title;
        }

        $dungeonRoute                 = DungeonRoute::factory()->create($attributes);
        $this->createdDungeonRoutes[] = $dungeonRoute;

        return $dungeonRoute;
    }

    private function createCollection(DungeonRouteCollectionFactory $factory, ?Carbon $updatedAt = null): DungeonRouteCollection
    {
        return $factory->create([
            'user_id'    => $this->creator()->id,
            'updated_at' => $updatedAt ?? Carbon::now(),
        ]);
    }

    /**
     * @param array<int, DungeonRoute> $dungeonRoutes In collection order.
     */
    private function addRoutes(DungeonRouteCollection $dungeonRouteCollection, array $dungeonRoutes): void
    {
        foreach ($dungeonRoutes as $order => $dungeonRoute) {
            DungeonRouteCollectionRoute::create([
                'dungeon_route_collection_id' => $dungeonRouteCollection->id,
                'dungeon_route_id'            => $dungeonRoute->id,
                'order'                       => $order,
            ]);
        }
    }

    private function updateUrl(DungeonRouteCollection $dungeonRouteCollection): string
    {
        return route('collections.update', ['dungeonRouteCollection' => $dungeonRouteCollection]);
    }
}
