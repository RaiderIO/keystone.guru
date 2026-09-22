<?php

namespace Tests\Feature\Controller\DungeonRoute;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('DungeonRoute')]
final class DungeonRouteDiscoverSearchTest extends PublicTestCase
{
    #[Test]
    public function search_givenGuest_redirectsPermanentlyToDungeonRouteSearch(): void
    {
        // Arrange
        $this->actingAsGuest();

        // Act
        $response = $this->get(route('dungeonroutes.search'));

        // Assert
        $response->assertStatus(301);
        $response->assertRedirect(route('dungeon.dungeonroute.search'));
    }

    #[Test]
    public function search_givenLegacyFilterQueryString_redirectsPermanentlyToDungeonRouteSearchWithoutIt(): void
    {
        // Arrange
        $this->actingAsGuest();

        // Act
        $response = $this->get(sprintf('%s?season=1&affixgroups=2&dungeons=3&title=foo', route('dungeonroutes.search')));

        // Assert
        $response->assertStatus(301);
        $response->assertRedirect(route('dungeon.dungeonroute.search'));
    }

    #[Test]
    public function search_givenOldAjaxSearchEndpoint_returnsNoRoute(): void
    {
        // Arrange
        $this->actingAsGuest();

        // Act
        $response = $this->get('/ajax/search?offset=0&limit=10');

        // Assert - 'ajax/{dungeonRoute}' only answers PATCH/DELETE on this URI now
        $response->assertStatus(405);
    }
}
