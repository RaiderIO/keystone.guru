<?php

namespace Tests\Feature\App\Logic\MapContext;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\User;
use App\Service\MapContext\MapContextServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\ProvidesDungeon;
use Tests\TestCases\PublicTestCase;

#[Group('MapContext')]
final class MapContextDungeonRouteTest extends PublicTestCase
{
    use ProvidesDungeon;

    /**
     * Guards #4538: the mdt export endpoint is gated on a signature minted here at page render,
     * because a session-based gate cannot work - embeds are rendered in third party iframes and
     * receive no cookies at all (#4532). If these urls stop being minted, or stop validating, Copy
     * MDT breaks everywhere.
     */
    #[Test]
    public function toArray_givenDungeonRoute_emitsSignedMdtExportUrlsThatValidate(): void
    {
        // Arrange
        $dungeonRoute = $this->createDungeonRoute();

        try {
            // Act
            $mapContext = app(MapContextServiceInterface::class)
                ->createMapContextDungeonRoute($dungeonRoute, User::MAP_FACADE_STYLE_SPLIT_FLOORS)
                ->toArray();

            // Assert
            foreach (['mdtExportUrl', 'mdtExportUrlUncached'] as $key) {
                $this->assertArrayHasKey($key, $mapContext);
                $this->assertStringContainsString($dungeonRoute->public_key, $mapContext[$key]);
                $this->assertTrue(
                    URL::hasValidSignature($this->requestFor($mapContext[$key]), absolute: false),
                    sprintf('%s did not carry a valid relative signature', $key),
                );
            }
        } finally {
            $dungeonRoute->delete();
        }
    }

    /**
     * The two urls differ only in useCache, which is part of the signed query string - that is what
     * stops a viewer from flipping the cheap cached export into the expensive uncached one the map
     * editor uses.
     */
    #[Test]
    public function toArray_givenDungeonRoute_emitsDistinctCachedAndUncachedMdtExportUrls(): void
    {
        // Arrange
        $dungeonRoute = $this->createDungeonRoute();

        try {
            // Act
            $mapContext = app(MapContextServiceInterface::class)
                ->createMapContextDungeonRoute($dungeonRoute, User::MAP_FACADE_STYLE_SPLIT_FLOORS)
                ->toArray();

            // Assert
            $this->assertStringContainsString('useCache=1', $mapContext['mdtExportUrl']);
            $this->assertStringContainsString('useCache=0', $mapContext['mdtExportUrlUncached']);
            $this->assertNotSame($mapContext['mdtExportUrl'], $mapContext['mdtExportUrlUncached']);
        } finally {
            $dungeonRoute->delete();
        }
    }

    private function createDungeonRoute(): DungeonRoute
    {
        [$dungeon, $mappingVersion] = $this->findDungeon(challengeMode: true);

        return DungeonRoute::factory()->create([
            'expires_at'         => null,
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $mappingVersion->id,
        ]);
    }

    /**
     * The map context mints relative urls, which hasValidSignature() can only check against a
     * Request - so rebuild one the way the framework would for that path.
     */
    private function requestFor(string $relativeUrl): Request
    {
        return Request::create($relativeUrl);
    }
}
