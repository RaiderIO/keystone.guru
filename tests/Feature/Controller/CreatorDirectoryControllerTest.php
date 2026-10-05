<?php

namespace Tests\Feature\Controller;

use App\Features\CreatorProfiles;
use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteCollection;
use App\Models\DungeonRoute\DungeonRouteCollectionCategoryType;
use App\Models\PublishedState;
use App\Models\Season;
use App\Models\User;
use App\Models\UserSocialLink;
use App\Models\UserSocialLinkPlatform;
use App\Service\Creator\CreatorDirectoryServiceInterface;
use App\Service\Creator\Enums\CreatorDirectorySort;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Str;
use Laravel\Pennant\Feature;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
final class CreatorDirectoryControllerTest extends PublicTestCase
{
    #[Test]
    public function index_givenFeatureInactive_returnsNotFound(): void
    {
        // Arrange
        $viewer = User::factory()->create();
        Feature::for($viewer)->deactivate(CreatorProfiles::class);

        try {
            // Act
            $response = $this->actingAs($viewer)->get(route('creators.index'));

            // Assert
            $response->assertNotFound();
        } finally {
            Feature::for($viewer)->forget(CreatorProfiles::class);
            $viewer->delete();
        }
    }

    #[Test]
    public function index_givenACreatorAboveTheThreshold_listsThem(): void
    {
        // Arrange
        $viewer  = User::factory()->create();
        $creator = User::factory()->create();
        $routes  = $this->createPublishedRoutesFor($creator, $this->minPublishedRoutes());

        Feature::for($viewer)->activate(CreatorProfiles::class);

        try {
            // Act
            $response = $this->actingAs($viewer)->get(route('creators.index'));

            // Assert
            $response->assertOk();
            $response->assertSee($creator->name);
            $this->assertTrue(
                $this->creatorIdsFrom($response)->contains($creator->id),
                'A creator at the threshold must be listed',
            );
        } finally {
            Feature::for($viewer)->forget(CreatorProfiles::class);
            $this->deleteAll($routes);
            $creator->delete();
            $viewer->delete();
        }
    }

    #[Test]
    public function index_givenACreatorBelowTheThreshold_doesNotListThem(): void
    {
        // Arrange
        $viewer  = User::factory()->create();
        $creator = $this->createSearchableCreator();
        $routes  = $this->createPublishedRoutesFor($creator, $this->minPublishedRoutes() - 1);

        Feature::for($viewer)->activate(CreatorProfiles::class);

        try {
            // Act - narrowed to the creator, so a regression cannot hide them on a later page
            $response = $this->actingAs($viewer)->get(route('creators.index', ['search' => $creator->name]));

            // Assert
            $response->assertOk();
            $this->assertFalse(
                $this->creatorIdsFrom($response)->contains($creator->id),
                'A creator below the threshold must not be listed',
            );
        } finally {
            Feature::for($viewer)->forget(CreatorProfiles::class);
            $this->deleteAll($routes);
            $creator->delete();
            $viewer->delete();
        }
    }

    /**
     * Listing is automatic, so the opt-out switch is the only thing standing between a creator and
     * a public listing they never asked for. If this regresses, the switch silently does nothing.
     */
    #[Test]
    public function index_givenACreatorWhoOptedOut_doesNotListThem(): void
    {
        // Arrange
        $viewer  = User::factory()->create();
        $creator = $this->createSearchableCreator(['hide_from_creator_directory' => true]);
        $routes  = $this->createPublishedRoutesFor($creator, $this->minPublishedRoutes());

        Feature::for($viewer)->activate(CreatorProfiles::class);

        try {
            // Act - narrowed to the creator, so a regression cannot hide them on a later page
            $response = $this->actingAs($viewer)->get(route('creators.index', ['search' => $creator->name]));

            // Assert
            $response->assertOk();
            $this->assertFalse(
                $this->creatorIdsFrom($response)->contains($creator->id),
                'A creator who opted out must never appear in the directory',
            );
        } finally {
            Feature::for($viewer)->forget(CreatorProfiles::class);
            $this->deleteAll($routes);
            $creator->delete();
            $viewer->delete();
        }
    }

    /**
     * Unpublished routes must not count towards the listing threshold, or a user with only private
     * drafts would be presented publicly as a route creator.
     */
    #[Test]
    public function index_givenOnlyUnpublishedRoutes_doesNotListTheCreator(): void
    {
        // Arrange
        $viewer  = User::factory()->create();
        $creator = $this->createSearchableCreator();
        $routes  = new EloquentCollection();

        for ($i = 0; $i < $this->minPublishedRoutes(); $i++) {
            $routes->push(DungeonRoute::factory()->create([
                'author_id'          => $creator->id,
                'expires_at'         => null,
                'published_state_id' => PublishedState::ALL[PublishedState::UNPUBLISHED],
            ]));
        }

        Feature::for($viewer)->activate(CreatorProfiles::class);

        try {
            // Act - narrowed to the creator, so a regression cannot hide them on a later page
            $response = $this->actingAs($viewer)->get(route('creators.index', ['search' => $creator->name]));

            // Assert
            $response->assertOk();
            $this->assertFalse(
                $this->creatorIdsFrom($response)->contains($creator->id),
                'Unpublished routes must not count towards the listing threshold',
            );
        } finally {
            Feature::for($viewer)->forget(CreatorProfiles::class);
            $this->deleteAll($routes);
            $creator->delete();
            $viewer->delete();
        }
    }

    #[Test]
    public function index_givenASearchTerm_onlyListsMatchingCreators(): void
    {
        // Arrange
        $viewer   = User::factory()->create();
        $wanted   = User::factory()->create(['name' => 'ZzTestCreatorWanted']);
        $unwanted = User::factory()->create(['name' => 'ZzTestCreatorOther']);

        $wantedRoutes   = $this->createPublishedRoutesFor($wanted, $this->minPublishedRoutes());
        $unwantedRoutes = $this->createPublishedRoutesFor($unwanted, $this->minPublishedRoutes());

        Feature::for($viewer)->activate(CreatorProfiles::class);

        try {
            // Act
            $response = $this->actingAs($viewer)->get(route('creators.index', ['search' => 'Wanted']));

            // Assert
            $response->assertOk();
            $creatorIds = $this->creatorIdsFrom($response);
            $this->assertTrue($creatorIds->contains($wanted->id), 'The matching creator must be listed');
            $this->assertFalse($creatorIds->contains($unwanted->id), 'A non-matching creator must be filtered out');
        } finally {
            Feature::for($viewer)->forget(CreatorProfiles::class);
            $this->deleteAll($wantedRoutes);
            $this->deleteAll($unwantedRoutes);
            $wanted->delete();
            $unwanted->delete();
            $viewer->delete();
        }
    }

    /**
     * The search term goes into a LIKE, so the wildcards have to be escaped - otherwise a search
     * for '%' would match every creator on the site.
     */
    #[Test]
    public function index_givenALikeWildcardAsTheSearch_doesNotMatchEveryone(): void
    {
        // Arrange
        $viewer  = User::factory()->create();
        $creator = User::factory()->create(['name' => 'ZzTestWildcardCreator']);
        $routes  = $this->createPublishedRoutesFor($creator, $this->minPublishedRoutes());

        Feature::for($viewer)->activate(CreatorProfiles::class);

        try {
            // Act
            $response = $this->actingAs($viewer)->get(route('creators.index', ['search' => '%']));

            // Assert
            $response->assertOk();
            $this->assertFalse(
                $this->creatorIdsFrom($response)->contains($creator->id),
                'A literal % must be treated as text, not as a LIKE wildcard',
            );
        } finally {
            Feature::for($viewer)->forget(CreatorProfiles::class);
            $this->deleteAll($routes);
            $creator->delete();
            $viewer->delete();
        }
    }

    #[Test]
    public function index_givenACategoryFilter_onlyListsCreatorsSharingThatKindOfCollection(): void
    {
        // Arrange
        $viewer   = User::factory()->create();
        $wanted   = User::factory()->create();
        $unwanted = User::factory()->create();

        $wantedRoutes   = $this->createPublishedRoutesFor($wanted, $this->minPublishedRoutes());
        $unwantedRoutes = $this->createPublishedRoutesFor($unwanted, $this->minPublishedRoutes());

        $wantedCollection = $this->createPublishedCollectionFor($wanted, DungeonRouteCollectionCategoryType::Mdi);
        // A collection of a different kind must not make its author match the MDI filter
        $unwantedCollection = $this->createPublishedCollectionFor($unwanted, DungeonRouteCollectionCategoryType::Beginner);

        Feature::for($viewer)->activate(CreatorProfiles::class);

        try {
            // Act
            $response = $this->actingAs($viewer)->get(route('creators.index', [
                'category_id' => DungeonRouteCollectionCategoryType::Mdi->id(),
            ]));

            // Assert
            $response->assertOk();
            $creatorIds = $this->creatorIdsFrom($response);
            $this->assertTrue($creatorIds->contains($wanted->id), 'A creator sharing that kind of collection must be listed');
            $this->assertFalse($creatorIds->contains($unwanted->id), 'A creator without one must be filtered out');
        } finally {
            Feature::for($viewer)->forget(CreatorProfiles::class);
            $unwantedCollection->delete();
            $wantedCollection->delete();
            $this->deleteAll($unwantedRoutes);
            $this->deleteAll($wantedRoutes);
            $unwanted->delete();
            $wanted->delete();
            $viewer->delete();
        }
    }

    /**
     * Matching on a collection nobody may see would leak that the collection exists at all, so
     * only world published collections may put a creator in a filtered listing.
     */
    #[Test]
    public function index_givenACategoryFilter_ignoresCollectionsThatAreNotPublic(): void
    {
        // Arrange
        $viewer  = User::factory()->create();
        $creator = $this->createSearchableCreator();
        $routes  = $this->createPublishedRoutesFor($creator, $this->minPublishedRoutes());

        $collection = $this->createPublishedCollectionFor($creator, DungeonRouteCollectionCategoryType::Expert);
        $collection->update(['published_state_id' => PublishedState::ALL[PublishedState::UNPUBLISHED]]);

        Feature::for($viewer)->activate(CreatorProfiles::class);

        try {
            // Act - narrowed to the creator, so a regression cannot hide them on a later page
            $response = $this->actingAs($viewer)->get(route('creators.index', [
                'category_id' => DungeonRouteCollectionCategoryType::Expert->id(),
                'search'      => $creator->name,
            ]));

            // Assert
            $response->assertOk();
            $this->assertFalse(
                $this->creatorIdsFrom($response)->contains($creator->id),
                'An unpublished collection must not surface its author in a filtered listing',
            );
        } finally {
            Feature::for($viewer)->forget(CreatorProfiles::class);
            $collection->delete();
            $this->deleteAll($routes);
            $creator->delete();
            $viewer->delete();
        }
    }

    /**
     * The category select posts an empty string for "All creators", which must browse unfiltered
     * rather than fail the integer rule.
     */
    #[Test]
    public function index_givenAnEmptyCategory_listsEveryCreator(): void
    {
        // Arrange
        $viewer  = User::factory()->create();
        $creator = User::factory()->create();
        $routes  = $this->createPublishedRoutesFor($creator, $this->minPublishedRoutes());

        Feature::for($viewer)->activate(CreatorProfiles::class);

        try {
            // Act
            $response = $this->actingAs($viewer)->get(route('creators.index', ['search' => '', 'category_id' => '']));

            // Assert
            $response->assertOk();
            $response->assertSessionHasNoErrors();
            $this->assertTrue(
                $this->creatorIdsFrom($response)->contains($creator->id),
                'An empty category means "any category", not a validation error',
            );
        } finally {
            Feature::for($viewer)->forget(CreatorProfiles::class);
            $this->deleteAll($routes);
            $creator->delete();
            $viewer->delete();
        }
    }

    #[Test]
    public function index_givenACategoryThatDoesNotExist_failsValidation(): void
    {
        // Arrange
        $viewer = User::factory()->create();
        Feature::for($viewer)->activate(CreatorProfiles::class);

        try {
            // Act
            $response = $this->actingAs($viewer)->get(route('creators.index', ['category_id' => 99999]));

            // Assert
            $response->assertSessionHasErrors('category_id');
        } finally {
            Feature::for($viewer)->forget(CreatorProfiles::class);
            $viewer->delete();
        }
    }

    #[Test]
    public function index_givenTheSeededCategories_offersEveryDifficulty(): void
    {
        // Arrange
        $viewer = User::factory()->create();
        Feature::for($viewer)->activate(CreatorProfiles::class);

        try {
            // Act
            $response = $this->actingAs($viewer)->get(route('creators.index'));

            // Assert
            $response->assertOk();
            $this->assertEqualsCanonicalizing(
                [
                    DungeonRouteCollectionCategoryType::Beginner->id(),
                    DungeonRouteCollectionCategoryType::Intermediate->id(),
                    DungeonRouteCollectionCategoryType::Expert->id(),
                    DungeonRouteCollectionCategoryType::Mdi->id(),
                ],
                $response->viewData('categories')->pluck('id')->all(),
            );
        } finally {
            Feature::for($viewer)->forget(CreatorProfiles::class);
            $viewer->delete();
        }
    }

    #[Test]
    public function index_givenTheSeededCategories_labelsTheFilterByWhatACreatorShares(): void
    {
        // Arrange
        $viewer = User::factory()->create();
        Feature::for($viewer)->activate(CreatorProfiles::class);

        try {
            // Act
            $response = $this->actingAs($viewer)->get(route('creators.index'));

            // Assert
            $response->assertOk();
            $response->assertSee(e(__('view_creator.directory.category_any')), false);
            $response->assertSee(e(__('view_creator.directory.category_option', [
                'category' => __('dungeonroutecollectioncategories.mdi'),
            ])), false);
            $response->assertSee(e(__('view_creator.directory.description', [
                'min' => $this->minPublishedRoutes(),
            ])), false);
        } finally {
            Feature::for($viewer)->forget(CreatorProfiles::class);
            $viewer->delete();
        }
    }

    #[Test]
    public function index_givenAnOverlongSearch_failsValidation(): void
    {
        // Arrange
        $viewer = User::factory()->create();
        Feature::for($viewer)->activate(CreatorProfiles::class);

        try {
            // Act
            $response = $this->actingAs($viewer)->get(route('creators.index', ['search' => str_repeat('a', 25)]));

            // Assert
            $response->assertSessionHasErrors('search');
        } finally {
            Feature::for($viewer)->forget(CreatorProfiles::class);
            $viewer->delete();
        }
    }

    #[Test]
    public function index_givenNoSort_sortsByActivityThisSeason(): void
    {
        // Arrange
        $viewer = User::factory()->create();
        Feature::for($viewer)->activate(CreatorProfiles::class);

        try {
            // Act
            $response = $this->actingAs($viewer)->get(route('creators.index'));

            // Assert
            $response->assertOk();
            $response->assertViewHas('sort', CreatorDirectorySort::ActiveThisSeason);
        } finally {
            Feature::for($viewer)->forget(CreatorProfiles::class);
            $viewer->delete();
        }
    }

    #[Test]
    public function index_givenTheMostRoutesSort_sortsByRouteCount(): void
    {
        // Arrange
        $viewer = User::factory()->create();
        Feature::for($viewer)->activate(CreatorProfiles::class);

        try {
            // Act
            $response = $this->actingAs($viewer)->get(route('creators.index', ['sort' => CreatorDirectorySort::MostRoutes->value]));

            // Assert
            $response->assertOk();
            $response->assertViewHas('sort', CreatorDirectorySort::MostRoutes);
        } finally {
            Feature::for($viewer)->forget(CreatorProfiles::class);
            $viewer->delete();
        }
    }

    #[Test]
    public function index_givenAnUnknownSort_failsValidation(): void
    {
        // Arrange
        $viewer = User::factory()->create();
        Feature::for($viewer)->activate(CreatorProfiles::class);

        try {
            // Act
            $response = $this->actingAs($viewer)->get(route('creators.index', ['sort' => 'views']));

            // Assert
            $response->assertSessionHasErrors('sort');
        } finally {
            Feature::for($viewer)->forget(CreatorProfiles::class);
            $viewer->delete();
        }
    }

    #[Test]
    public function index_givenACreatorWithRoutesThisSeason_rendersTheirSeasonStatLine(): void
    {
        // Arrange - searched for by name, so the creator is on the first page whatever else is listed
        $viewer      = User::factory()->create();
        $creator     = $this->createSearchableCreator();
        $statsSeason = app(CreatorDirectoryServiceInterface::class)->getStatsSeason();
        $this->assertNotNull($statsSeason, 'Expected the default game version to have a current season');

        $routes = $this->createPublishedRoutesFor($creator, $this->minPublishedRoutes());
        foreach ($routes as $route) {
            $route->update(['season_id' => $statsSeason->id, 'views' => 1000]);
        }

        Feature::for($viewer)->activate(CreatorProfiles::class);

        try {
            // Act
            $response = $this->actingAs($viewer)->get(route('creators.index', ['search' => $creator->name]));

            // Assert
            $response->assertOk();
            $response->assertViewHas('statsSeason', $statsSeason);
            $response->assertSee(sprintf(
                '%s · %s',
                trans_choice('view_creator.stats.season_route_count', $this->minPublishedRoutes(), ['count' => $this->minPublishedRoutes()]),
                trans_choice('view_creator.stats.views', 1000 * $this->minPublishedRoutes(), ['views' => abbreviateNumber(1000 * $this->minPublishedRoutes())]),
            ));
        } finally {
            Feature::for($viewer)->forget(CreatorProfiles::class);
            $this->deleteAll($routes);
            $creator->delete();
            $viewer->delete();
        }
    }

    /**
     * The card is one stretched link to the profile, so a social link must sit beside that link, never inside it: an
     * anchor inside an anchor is invalid HTML that browsers split apart.
     */
    #[Test]
    public function index_givenACreatorWithSocialLinks_rendersEachBesideTheProfileLink(): void
    {
        // Arrange - searched for by name, so the creator is on the first page whatever else is listed
        $viewer      = User::factory()->create();
        $creator     = $this->createSearchableCreator();
        $routes      = $this->createPublishedRoutesFor($creator, $this->minPublishedRoutes());
        $socialLinks = collect([
            UserSocialLinkPlatform::Twitch->value  => 'https://twitch.tv/someone',
            UserSocialLinkPlatform::Youtube->value => 'https://youtube.com/@someone',
        ])->map(static fn(string $url, string $platform): UserSocialLink => UserSocialLink::create([
            'user_id'  => $creator->id,
            'platform' => $platform,
            'url'      => $url,
        ]));

        Feature::for($viewer)->activate(CreatorProfiles::class);

        try {
            // Act
            $response = $this->actingAs($viewer)->get(route('creators.index', ['search' => $creator->name]));

            // Assert
            $response->assertOk();
            $profileLinkPattern = sprintf(
                '#<a href="%s"\s+class="creator_card_name stretched-link[^"]*">(.*?)</a>#s',
                preg_quote(route('profile.view', ['user' => $creator]), '#'),
            );
            $this->assertSame(1, preg_match($profileLinkPattern, $response->getContent(), $profileLink));
            $this->assertSame($creator->name, trim($profileLink[1]));
            $response->assertSeeInOrder([
                sprintf('href="%s"', 'https://twitch.tv/someone'),
                sprintf('aria-label="%s"', e(__('view_profile.view.social_link', ['platform' => __('view_profile.view.platform.twitch')]))),
                sprintf('href="%s"', 'https://youtube.com/@someone'),
                sprintf('aria-label="%s"', e(__('view_profile.view.social_link', ['platform' => __('view_profile.view.platform.youtube')]))),
            ], false);
        } finally {
            Feature::for($viewer)->forget(CreatorProfiles::class);
            $socialLinks->each(static fn(UserSocialLink $socialLink) => $socialLink->delete());
            $this->deleteAll($routes);
            $creator->delete();
            $viewer->delete();
        }
    }

    #[Test]
    public function index_givenACreatorWithoutSocialLinks_rendersNoSocialsRow(): void
    {
        // Arrange - a creator with a link on the same page is the control that the row renders at all
        $viewer        = User::factory()->create();
        $creator       = $this->createSearchableCreator();
        $linkedCreator = $this->createSearchableCreator(['name' => sprintf('%sLinked', $creator->name)]);
        $routes        = $this->createPublishedRoutesFor($creator, $this->minPublishedRoutes());
        $routes->push(...$this->createPublishedRoutesFor($linkedCreator, $this->minPublishedRoutes()));
        $socialLink = UserSocialLink::create([
            'user_id'  => $linkedCreator->id,
            'platform' => UserSocialLinkPlatform::Twitch->value,
            'url'      => 'https://twitch.tv/someone',
        ]);

        Feature::for($viewer)->activate(CreatorProfiles::class);

        try {
            // Act
            $response = $this->actingAs($viewer)->get(route('creators.index', ['search' => $creator->name]));

            // Assert
            $response->assertOk();
            $this->assertEqualsCanonicalizing([$creator->id, $linkedCreator->id], $this->creatorIdsFrom($response)->all());
            $this->assertSame(1, substr_count($response->getContent(), 'creator_card_socials'));
        } finally {
            Feature::for($viewer)->forget(CreatorProfiles::class);
            $socialLink->delete();
            $this->deleteAll($routes);
            $linkedCreator->delete();
            $creator->delete();
            $viewer->delete();
        }
    }

    /**
     * Only world-published routes for the dungeon in its current season qualify a creator - a link-only or
     * unpublished route there must not, or the filter would reveal that route exists.
     */
    #[Test]
    public function index_givenADungeon_listsOnlyCreatorsWithWorldPublishedSeasonRoutesThere(): void
    {
        // Arrange - every creator is listed site-wide, so only the dungeon filter can tell them apart
        config(['keystoneguru.creators.per_page' => PHP_INT_MAX]);
        $viewer   = User::factory()->create();
        $dungeons = $this->statsSeason()->dungeons;
        $dungeon  = $dungeons[0];
        $maker    = $this->createSearchableCreator();
        $other    = $this->createSearchableCreator();
        $routes   = $this->createRoutesFor($maker, $dungeon, $this->minPublishedRoutes());
        $routes->push(...$this->createRoutesFor($other, $dungeons[1], $this->minPublishedRoutes()));
        $routes->push(...$this->createRoutesFor($other, $dungeon, 1, PublishedState::WORLD_WITH_LINK));
        $routes->push(...$this->createRoutesFor($other, $dungeon, 1, PublishedState::UNPUBLISHED));

        Feature::for($viewer)->activate(CreatorProfiles::class);

        try {
            // Act
            $response = $this->actingAs($viewer)->get(route('creators.index', ['dungeon' => $dungeon->slug]));

            // Assert
            $response->assertOk();
            $response->assertViewHas('selectedDungeon', static fn(?Dungeon $selectedDungeon): bool => $selectedDungeon?->id === $dungeon->id);
            $creatorIds = $this->creatorIdsFrom($response);
            $this->assertContains($maker->id, $creatorIds);
            $this->assertNotContains($other->id, $creatorIds);
        } finally {
            Feature::for($viewer)->forget(CreatorProfiles::class);
            $this->deleteAll($routes);
            $maker->delete();
            $other->delete();
            $viewer->delete();
        }
    }

    #[Test]
    public function index_givenADungeon_ranksCreatorsByPopularityThere(): void
    {
        // Arrange - the less popular creator has more routes, so a route-count ordering would flip them
        config(['keystoneguru.creators.per_page' => PHP_INT_MAX]);
        $viewer    = User::factory()->create();
        $dungeon   = $this->statsSeason()->dungeons[0];
        $popular   = $this->createSearchableCreator();
        $unpopular = $this->createSearchableCreator();
        $routes    = $this->createRoutesFor($popular, $dungeon, $this->minPublishedRoutes(), PublishedState::WORLD, ['popularity' => 50]);
        $routes->push(...$this->createRoutesFor($unpopular, $dungeon, $this->minPublishedRoutes() + 1, PublishedState::WORLD, ['popularity' => 1]));

        Feature::for($viewer)->activate(CreatorProfiles::class);

        try {
            // Act - the routes sort asked for must not override the dungeon's ranking
            $response = $this->actingAs($viewer)->get(route('creators.index', [
                'dungeon' => $dungeon->slug,
                'sort'    => CreatorDirectorySort::MostRoutes->value,
            ]));

            // Assert
            $response->assertOk();
            $creatorIds = $this->creatorIdsFrom($response);
            $this->assertContains($popular->id, $creatorIds);
            $this->assertLessThan($creatorIds->search($unpopular->id), $creatorIds->search($popular->id));
        } finally {
            Feature::for($viewer)->forget(CreatorProfiles::class);
            $this->deleteAll($routes);
            $popular->delete();
            $unpopular->delete();
            $viewer->delete();
        }
    }

    #[Test]
    public function index_givenADungeon_saysWhichDungeonAndLinksToClearIt(): void
    {
        // Arrange
        $viewer  = User::factory()->create();
        $dungeon = $this->statsSeason()->dungeons[0];
        Feature::for($viewer)->activate(CreatorProfiles::class);

        try {
            // Act
            $response = $this->actingAs($viewer)->get(route('creators.index', ['dungeon' => $dungeon->slug]));

            // Assert - the clear link drops the dungeon and nothing else, so it lands on the unfiltered directory
            $response->assertOk();
            $response->assertSee(e(__('view_creator.directory.filtered_to_dungeon', ['dungeon' => __($dungeon->name)])), false);
            $this->assertMatchesRegularExpression(
                sprintf('/<a href="%s"\s+class="ms-2 text-nowrap">/', preg_quote(route('creators.index'), '/')),
                (string)$response->getContent(),
            );
            $response->assertSee(sprintf('name="dungeon" value="%s"', e($dungeon->slug)), false);
        } finally {
            Feature::for($viewer)->forget(CreatorProfiles::class);
            $viewer->delete();
        }
    }

    #[Test]
    public function index_givenADungeonNobodyPublishesRoutesFor_rendersTheEmptyMessageForIt(): void
    {
        // Arrange
        $viewer = User::factory()->create();
        /** @var Dungeon|null $dungeon */
        $dungeon = Dungeon::query()
            ->where('slug', '!=', '')
            ->whereDoesntHave('dungeonRoutes', static fn($builder) => $builder->where('published_state_id', PublishedState::ALL[PublishedState::WORLD]))
            ->first();
        $this->assertNotNull($dungeon, 'Expected a seeded dungeon without world-published routes');
        Feature::for($viewer)->activate(CreatorProfiles::class);

        try {
            // Act
            $response = $this->actingAs($viewer)->get(route('creators.index', ['dungeon' => $dungeon->slug]));

            // Assert
            $response->assertOk();
            $this->assertTrue($this->creatorIdsFrom($response)->isEmpty());
            $response->assertSee(e(__('view_creator.directory.empty_for_dungeon', ['dungeon' => __($dungeon->name)])), false);
        } finally {
            Feature::for($viewer)->forget(CreatorProfiles::class);
            $viewer->delete();
        }
    }

    /**
     * The dungeon is carried through every search, so an unmatched name inside a dungeon filter must say the search
     * found no one rather than claim nobody publishes routes for the dungeon.
     */
    #[Test]
    public function index_givenADungeonAndASearchNobodyMatches_rendersTheEmptyMessageForTheSearch(): void
    {
        // Arrange
        $viewer = User::factory()->create();
        $search = sprintf('nobody-%s', Str::random(12));
        /** @var Dungeon|null $dungeon */
        $dungeon = Dungeon::query()
            ->where('slug', '!=', '')
            ->whereDoesntHave('dungeonRoutes', static fn($builder) => $builder->where('published_state_id', PublishedState::ALL[PublishedState::WORLD]))
            ->first();
        $this->assertNotNull($dungeon, 'Expected a seeded dungeon without world-published routes');
        Feature::for($viewer)->activate(CreatorProfiles::class);

        try {
            // Act
            $response = $this->actingAs($viewer)->get(route('creators.index', ['dungeon' => $dungeon->slug, 'search' => $search]));

            // Assert
            $response->assertOk();
            $this->assertTrue($this->creatorIdsFrom($response)->isEmpty());
            $response->assertSee(e(__('view_creator.directory.empty_for_search', ['search' => $search])), false);
            $response->assertDontSee(e(__('view_creator.directory.empty_for_dungeon', ['dungeon' => __($dungeon->name)])), false);
        } finally {
            Feature::for($viewer)->forget(CreatorProfiles::class);
            $viewer->delete();
        }
    }

    #[Test]
    public function index_givenAnUnknownDungeon_failsValidation(): void
    {
        // Arrange
        $viewer = User::factory()->create();
        Feature::for($viewer)->activate(CreatorProfiles::class);

        try {
            // Act
            $response = $this->actingAs($viewer)->get(route('creators.index', ['dungeon' => 'not-a-dungeon']));

            // Assert
            $response->assertSessionHasErrors('dungeon');
        } finally {
            Feature::for($viewer)->forget(CreatorProfiles::class);
            $viewer->delete();
        }
    }

    private function statsSeason(): Season
    {
        $statsSeason = app(CreatorDirectoryServiceInterface::class)->getStatsSeason();
        $this->assertNotNull($statsSeason, 'Expected the default game version to have a current season');
        $this->assertGreaterThanOrEqual(4, $statsSeason->dungeons->count(), 'Expected the current season to have at least four dungeons');

        return $statsSeason;
    }

    /**
     * Routes for a dungeon in the stats season, which is the dungeon's own current season as well.
     *
     * @param array<string, mixed> $attributes
     *
     * @return EloquentCollection<int, DungeonRoute>
     */
    private function createRoutesFor(
        User    $creator,
        Dungeon $dungeon,
        int     $count,
        string  $publishedState = PublishedState::WORLD,
        array   $attributes = [],
    ): EloquentCollection {
        $routes = new EloquentCollection();

        for ($i = 0; $i < $count; $i++) {
            $routes->push(DungeonRoute::factory()->create(array_merge([
                'author_id'          => $creator->id,
                'dungeon_id'         => $dungeon->id,
                'season_id'          => $this->statsSeason()->id,
                'expires_at'         => null,
                'published_state_id' => PublishedState::ALL[$publishedState],
            ], $attributes)));
        }

        return $routes;
    }

    /**
     * A creator whose whole name passes the search's length cap, which a faker name does not always do.
     *
     * @param array<string, mixed> $attributes
     */
    private function createSearchableCreator(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'name' => sprintf('ZzTestCreator%d', random_int(100000, 999999)),
        ], $attributes));
    }

    private function minPublishedRoutes(): int
    {
        return (int)config('keystoneguru.creators.min_published_routes');
    }

    /** @return EloquentCollection<int, DungeonRoute> */
    private function createPublishedRoutesFor(User $creator, int $count): EloquentCollection
    {
        $routes = new EloquentCollection();

        for ($i = 0; $i < $count; $i++) {
            $routes->push(DungeonRoute::factory()->create([
                'author_id'          => $creator->id,
                'expires_at'         => null,
                'published_state_id' => PublishedState::ALL[PublishedState::WORLD],
            ]));
        }

        return $routes;
    }

    private function createPublishedCollectionFor(User $creator, DungeonRouteCollectionCategoryType $categoryType): DungeonRouteCollection
    {
        return DungeonRouteCollection::factory()->create([
            'user_id'                              => $creator->id,
            'published_state_id'                   => PublishedState::ALL[PublishedState::WORLD],
            'dungeon_route_collection_category_id' => $categoryType->id(),
        ]);
    }

    /** @param EloquentCollection<int, DungeonRoute> $routes */
    private function deleteAll(EloquentCollection $routes): void
    {
        foreach ($routes as $route) {
            $route->delete();
        }
    }

    /**
     * The ids on the rendered page of the directory, read off the view rather than the HTML. Only
     * the current page: a test asserting a creator is absent narrows the listing with a search first.
     *
     * @param \Illuminate\Testing\TestResponse<\Symfony\Component\HttpFoundation\Response> $response
     *
     * @return \Illuminate\Support\Collection<int, int>
     */
    private function creatorIdsFrom(\Illuminate\Testing\TestResponse $response): \Illuminate\Support\Collection
    {
        /** @var \Illuminate\Pagination\LengthAwarePaginator<int, User> $creators */
        $creators = $response->viewData('creators');

        return collect($creators->items())->pluck('id');
    }
}
