<?php

namespace Tests\Feature\View\Common\Group;

use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\ProvidesDungeon;
use Tests\TestCases\PublicTestCase;

/**
 * The group composition is rendered in the route settings modal and again in the lazily loaded create route
 * modal, so a page can hold two instances of its inline code. Looking it up by blade path then answers an
 * array instead of an instance, so each rendered copy has to look up its own instance by id.
 */
#[Group('View')]
final class CompositionInlineCodeLookupTest extends PublicTestCase
{
    use ProvidesDungeon;

    #[Test]
    public function edit_givenTheRouteSettingsComposition_looksItUpByItsOwnInlineId(): void
    {
        // Arrange
        $owner        = null;
        $dungeonRoute = null;

        try {
            $owner                      = User::factory()->create();
            [$dungeon, $mappingVersion] = $this->findDungeon(
                facadeEnabled:       false,
                dungeonActive:       true,
                requireDefaultFloor: true,
                resolve:             static fn(Dungeon $dungeon) => true,
            );
            $dungeonRoute = DungeonRoute::factory()->create([
                'author_id'          => $owner->id,
                'dungeon_id'         => $dungeon->id,
                'mapping_version_id' => $mappingVersion->id,
                'expires_at'         => null,
            ]);

            // Act
            $response = $this->actingAs($owner)->followingRedirects()->get(route('dungeonroute.edit', [
                'dungeon'      => $dungeonRoute->dungeon,
                'dungeonroute' => $dungeonRoute,
                'title'        => $dungeonRoute->getTitleSlug(),
            ]));

            // Assert - one lookup sets the route's defaults, the other loads them when the advanced options open
            $response->assertOk();
            $content = $response->getContent();
            $this->assertSame(1, preg_match("/_inlineManager\\.init\\('([0-9a-f]+)', 'common\\/group\\/composition'/", $content, $matches));
            $this->assertSame(2, substr_count($content, sprintf("_inlineManager.getInlineCodeById('%s')", $matches[1])));
            $this->assertStringNotContainsString("getInlineCode('common/group/composition')", $content);
        } finally {
            $dungeonRoute?->delete();
            $owner?->delete();
        }
    }
}
