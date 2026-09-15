<?php

namespace Tests\Feature\Controller\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\ProvidesDungeon;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('DungeonRoute')]
final class DungeonRoutePreviewTest extends PublicTestCase
{
    use ProvidesDungeon;

    private const string PREVIEW_SECRET = 'test-preview-secret';

    /**
     * The preview page is what puppeteer screenshots for thumbnails. #app is min-height based (required
     * for the sticky site header, #3851), which ends the percentage-height chain that sizes #map - so the
     * map only has a height if it sits inside .wrapper, which carries a definite 100dvh. Without it the
     * map collapses to 0 and every thumbnail renders as a blank page background (#4101).
     */
    #[Test]
    public function preview_givenValidSecret_rendersTheMapInsideAHeightCarryingWrapper(): void
    {
        // Arrange
        config(['keystoneguru.thumbnail.preview_secret' => self::PREVIEW_SECRET]);

        [$dungeon, $mappingVersion] = $this->findDungeon();

        $dungeonRoute = DungeonRoute::factory()->create([
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $mappingVersion->id,
        ]);

        try {
            // Act
            $response = $this->get(route('dungeonroute.preview', [
                'dungeon'      => $dungeon,
                'dungeonroute' => $dungeonRoute->public_key,
                'title'        => $dungeonRoute->getTitleSlug(),
                'floorIndex'   => $dungeon->floors()->active()->firstOrFail()->index,
                'secret'       => self::PREVIEW_SECRET,
            ]));

            // Assert
            $response->assertOk();

            $content        = $response->getContent();
            $wrapperOpensAt = strpos($content, 'class="wrapper"');
            $mapOpensAt     = strpos($content, 'id="map"');

            $this->assertNotFalse($wrapperOpensAt, 'The preview page must render a .wrapper - without it #map has no height and the thumbnail renders blank.');
            $this->assertNotFalse($mapOpensAt, 'The preview page must render #map.');
            $this->assertLessThan($mapOpensAt, $wrapperOpensAt, 'The .wrapper must enclose #map, not follow it.');
        } finally {
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function preview_givenWrongSecret_isForbidden(): void
    {
        // Arrange
        config(['keystoneguru.thumbnail.preview_secret' => self::PREVIEW_SECRET]);

        [$dungeon, $mappingVersion] = $this->findDungeon();

        $dungeonRoute = DungeonRoute::factory()->create([
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $mappingVersion->id,
        ]);

        try {
            // Act
            $response = $this->get(route('dungeonroute.preview', [
                'dungeon'      => $dungeon,
                'dungeonroute' => $dungeonRoute->public_key,
                'title'        => $dungeonRoute->getTitleSlug(),
                'floorIndex'   => $dungeon->floors()->active()->firstOrFail()->index,
                'secret'       => 'not-the-secret',
            ]));

            // Assert
            $response->assertForbidden();
        } finally {
            $dungeonRoute->delete();
        }
    }
}
