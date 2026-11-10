<?php

namespace Tests\Feature\View\Common\General;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\PublishedState;
use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\ProvidesDungeon;
use Tests\TestCases\PublicTestCase;

#[Group('View')]
#[Group('ScriptJsonEncoding')]
final class ScriptJsonEncodingTest extends PublicTestCase
{
    use ProvidesDungeon;

    private const string RAW_MARKUP = '</script><!--x';
    /** @var string RAW_MARKUP as json_encode() writes it with JSON_HEX_TAG */
    private const string ENCODED_MARKUP = '\\' . 'u003C\/script\\' . 'u003E\\' . 'u003C!--x';

    #[Test]
    public function edit_givenRouteDescriptionWithMarkup_rendersItEncodedInTheMapContext(): void
    {
        $owner = null;
        $route = null;

        try {
            // Arrange
            $owner = User::factory()->create();
            $route = $this->createRoute($owner, ['description' => self::RAW_MARKUP]);

            // Act
            $response = $this->actingAs($owner)->followingRedirects()->get($this->routeUrl('dungeonroute.edit', $route));

            // Assert
            $response->assertOk();
            $response->assertSee(sprintf('"teamId":null,"description":"%s"', self::ENCODED_MARKUP), false);
            $response->assertDontSee('"teamId":null,"description":"<', false);
        } finally {
            $route?->delete();
            $owner?->delete();
        }
    }

    #[Test]
    public function edit_givenRouteDescriptionWithMarkup_rendersItEncodedInTheInlineCodeOptions(): void
    {
        $owner = null;
        $route = null;

        try {
            // Arrange
            $owner = User::factory()->create();
            $route = $this->createRoute($owner, ['description' => self::RAW_MARKUP]);

            // Act
            $response = $this->actingAs($owner)->followingRedirects()->get($this->routeUrl('dungeonroute.edit', $route));

            // Assert
            $response->assertOk();
            $response->assertSee(sprintf('"description":"%s","level_min"', self::ENCODED_MARKUP), false);
            $response->assertDontSee('"description":"<', false);
        } finally {
            $route?->delete();
            $owner?->delete();
        }
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function createRoute(User $owner, array $attributes = []): DungeonRoute
    {
        [$dungeon, $mappingVersion] = $this->findDungeon(
            facadeEnabled:       false,
            dungeonActive:       true,
            requireDefaultFloor: true,
        );

        return DungeonRoute::factory()->create(array_merge([
            'author_id'          => $owner->id,
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $mappingVersion->id,
            'expires_at'         => null,
            'published_state_id' => PublishedState::ALL[PublishedState::WORLD],
        ], $attributes));
    }

    private function routeUrl(string $routeName, DungeonRoute $route): string
    {
        return route($routeName, [
            'dungeon'      => $route->dungeon,
            'dungeonroute' => $route,
            'title'        => $route->getTitleSlug(),
        ]);
    }
}
