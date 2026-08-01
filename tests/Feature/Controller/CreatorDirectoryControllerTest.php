<?php

namespace Tests\Feature\Controller;

use App\Features\CreatorProfiles;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\PublishedState;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
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
        $creator = User::factory()->create();
        $routes  = $this->createPublishedRoutesFor($creator, $this->minPublishedRoutes() - 1);

        Feature::for($viewer)->activate(CreatorProfiles::class);

        try {
            // Act
            $response = $this->actingAs($viewer)->get(route('creators.index'));

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
        $creator = User::factory()->create(['hide_from_creator_directory' => true]);
        $routes  = $this->createPublishedRoutesFor($creator, $this->minPublishedRoutes());

        Feature::for($viewer)->activate(CreatorProfiles::class);

        try {
            // Act
            $response = $this->actingAs($viewer)->get(route('creators.index'));

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
        $creator = User::factory()->create();
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
            // Act
            $response = $this->actingAs($viewer)->get(route('creators.index'));

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

    /** @param EloquentCollection<int, DungeonRoute> $routes */
    private function deleteAll(EloquentCollection $routes): void
    {
        foreach ($routes as $route) {
            $route->delete();
        }
    }

    /**
     * The ids actually rendered into the directory, read off the view rather than the HTML so a
     * creator on a later page is not mistaken for one that was filtered out.
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
