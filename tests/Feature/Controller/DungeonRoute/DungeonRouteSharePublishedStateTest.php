<?php

namespace Tests\Feature\Controller\DungeonRoute;

use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Mapping\MappingVersion;
use App\Models\PublishedState;
use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\ProvidesDungeon;
use Tests\TestCases\PublicTestCase;

/**
 * The route editor's share modal renders its visibility picker through common.forms.publishedstate,
 * the same component the collection form uses.
 */
#[Group('Controller')]
#[Group('DungeonRoute')]
final class DungeonRouteSharePublishedStateTest extends PublicTestCase
{
    use ProvidesDungeon;

    #[Test]
    public function edit_givenAWorldPublishedRoute_rendersThePickerWithTheRouteSelected(): void
    {
        // Arrange
        $owner = User::factory()->create();
        $this->be($owner);

        [$dungeon, $mappingVersion] = $this->findDungeon(challengeMode: true, requireDefaultFloor: true);
        $route                      = $this->createRoute($owner, $dungeon, $mappingVersion);

        try {
            // Act
            $response = $this->followingRedirects()->get($this->editUrl($route));

            // Assert
            $response->assertOk();
            $content = (string)$response->getContent();

            $this->assertMatchesRegularExpression(
                '/<select id="map_route_publish" name="map_route_publish" class="form-control selectpicker"/',
                $content,
            );
            $this->assertMatchesRegularExpression('/<option value="world"[^>]*\sselected/', $content);
            $response->assertSee(e(__('js.publish_state_subtext_team')), false);
        } finally {
            $route->delete();
            $owner->delete();
        }
    }

    #[Test]
    public function edit_givenAUserWithoutTheUnlistedRoutesBenefit_disablesPublicWithLink(): void
    {
        // Arrange
        $owner = User::factory()->create();
        $this->be($owner);

        [$dungeon, $mappingVersion] = $this->findDungeon(challengeMode: true, requireDefaultFloor: true);
        $route                      = $this->createRoute($owner, $dungeon, $mappingVersion);

        try {
            // Act
            $response = $this->followingRedirects()->get($this->editUrl($route));

            // Assert
            $response->assertOk();
            $this->assertMatchesRegularExpression(
                '/<option value="world_with_link"[^>]*\sdisabled/',
                (string)$response->getContent(),
            );
        } finally {
            $route->delete();
            $owner->delete();
        }
    }

    private function createRoute(User $owner, Dungeon $dungeon, MappingVersion $mappingVersion): DungeonRoute
    {
        return DungeonRoute::factory()->create([
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $mappingVersion->id,
            'author_id'          => $owner->id,
            'expires_at'         => null,
            'published_state_id' => PublishedState::ALL[PublishedState::WORLD],
        ]);
    }

    private function editUrl(DungeonRoute $route): string
    {
        return route('dungeonroute.edit', [
            'dungeon'      => $route->dungeon,
            'dungeonroute' => $route,
            'title'        => $route->getTitleSlug(),
        ]);
    }
}
