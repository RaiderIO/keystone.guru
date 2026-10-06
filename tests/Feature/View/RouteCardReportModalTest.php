<?php

namespace Tests\Feature\View;

use App\Features\CreatorProfiles;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteCollection;
use App\Models\DungeonRoute\DungeonRouteCollectionRoute;
use App\Models\GameVersion\GameVersion;
use App\Models\Mapping\MappingVersion;
use App\Models\PublishedState;
use App\Models\User;
use App\Models\UserPinnedDungeonRoute;
use DOMDocument;
use DOMXPath;
use Laravel\Pennant\Feature;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * Every route card carries a Report entry that opens #userreport_dungeonroute_modal, so every page that
 * renders a route card must also render that modal - otherwise the entry does nothing.
 */
#[Group('View')]
#[Group('UserReport')]
final class RouteCardReportModalTest extends PublicTestCase
{
    #[Test]
    public function homePopularRoutes_givenARoute_rendersTheReportModalForItsCard(): void
    {
        // Arrange
        $dungeonRoute = DungeonRoute::factory()->create();

        try {
            // Act
            $xpath = $this->parse(view('home.sections.routes.popular', [
                'dungeonRoutes' => collect([$dungeonRoute]),
            ])->render());

            // Assert
            $this->assertCardReportEntryCount(1, $xpath, $dungeonRoute);
            $this->assertReportModalCount(1, $xpath);
        } finally {
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function home_givenAGuest_rendersTheReportModal(): void
    {
        // Arrange
        $this->actingAsGuest();

        // Act
        $response = $this->get(route('home'));

        // Assert
        $response->assertOk();
        $this->assertReportModalCount(1, $this->parse($response->getContent()));
    }

    #[Test]
    public function profileView_givenAPinnedRoute_rendersTheReportModalForItsCard(): void
    {
        // Arrange
        $creator = User::factory()->create();
        $viewer  = User::factory()->create();

        $dungeonRoute = DungeonRoute::factory()->create([
            'author_id'          => $creator->id,
            'expires_at'         => null,
            'published_state_id' => PublishedState::ALL[PublishedState::WORLD],
        ]);

        $pin                   = new UserPinnedDungeonRoute();
        $pin->user_id          = $creator->id;
        $pin->dungeon_route_id = $dungeonRoute->id;
        $pin->order            = 0;
        $pin->save();

        Feature::for($viewer)->activate(CreatorProfiles::class);

        try {
            // Act
            $response = $this->actingAs($viewer)->get(route('profile.view', ['user' => $creator]));

            // Assert
            $response->assertOk();
            $xpath = $this->parse($response->getContent());
            $this->assertCardReportEntryCount(1, $xpath, $dungeonRoute);
            $this->assertReportModalCount(1, $xpath);
        } finally {
            Feature::for($viewer)->forget(CreatorProfiles::class);
            $pin->delete();
            $dungeonRoute->delete();
            $viewer->delete();
            $creator->delete();
        }
    }

    #[Test]
    public function collectionView_givenARoute_rendersTheReportModalForItsCard(): void
    {
        // Arrange
        $creator        = User::factory()->create();
        $mappingVersion = MappingVersion::query()
            ->where('game_version_id', GameVersion::ALL[GameVersion::GAME_VERSION_RETAIL])
            ->whereHas('dungeon', static fn($query) => $query->whereNotNull('challenge_mode_id'))
            ->orderByDesc('id')
            ->firstOrFail();

        $dungeonRoute = DungeonRoute::factory()->create([
            'author_id'          => $creator->id,
            'dungeon_id'         => $mappingVersion->dungeon_id,
            'mapping_version_id' => $mappingVersion->id,
            'season_id'          => null,
            'expires_at'         => null,
            'published_state_id' => PublishedState::ALL[PublishedState::WORLD],
        ]);

        $dungeonRouteCollection = DungeonRouteCollection::factory()->create([
            'user_id'            => $creator->id,
            'published_state_id' => PublishedState::ALL[PublishedState::WORLD],
        ]);
        DungeonRouteCollectionRoute::create([
            'dungeon_route_collection_id' => $dungeonRouteCollection->id,
            'dungeon_route_id'            => $dungeonRoute->id,
            'order'                       => 0,
        ]);

        Feature::for(null)->activate(CreatorProfiles::class);

        try {
            // Act
            $response = $this->get(route('collection.view', ['dungeonRouteCollection' => $dungeonRouteCollection]));

            // Assert
            $response->assertOk();
            $xpath = $this->parse($response->getContent());
            $this->assertCardReportEntryCount(1, $xpath, $dungeonRoute);
            $this->assertReportModalCount(1, $xpath);
        } finally {
            Feature::for(null)->forget(CreatorProfiles::class);
            DungeonRouteCollectionRoute::query()
                ->where('dungeon_route_collection_id', $dungeonRouteCollection->id)
                ->delete();
            $dungeonRouteCollection->delete();
            $dungeonRoute->delete();
            $creator->delete();
        }
    }

    private function parse(string $html): DOMXPath
    {
        $document = new DOMDocument();
        @$document->loadHTML(sprintf('<?xml encoding="UTF-8">%s', $html));

        return new DOMXPath($document);
    }

    private function assertCardReportEntryCount(int $expected, DOMXPath $xpath, DungeonRoute $dungeonRoute): void
    {
        $this->assertSame(
            $expected,
            $xpath->query(sprintf(
                '//*[@data-bs-target="#userreport_dungeonroute_modal"][@data-publickey="%s"]',
                $dungeonRoute->public_key,
            ))->length,
            'The route card should carry a Report entry pointing at the report modal',
        );
    }

    private function assertReportModalCount(int $expected, DOMXPath $xpath): void
    {
        $this->assertSame(
            $expected,
            $xpath->query('//div[@id="userreport_dungeonroute_modal"][contains(concat(" ", @class, " "), " modal ")]')->length,
            'The page should render the report modal the route card Report entry opens',
        );
    }
}
