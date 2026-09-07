<?php

namespace Tests\Feature\Controller\Ajax;

use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\ProvidesDungeon;
use Tests\TestCases\AjaxPublicTestCase;

/**
 * The MDT export endpoint must only answer requests made by one of our own pages. Every request here
 * carries a valid signed url (#4538), so a 403 can only come from SameOriginOnly.
 */
#[Group('Controller')]
#[Group('DungeonRoute')]
final class MdtExportSameOriginTest extends AjaxPublicTestCase
{
    use ProvidesDungeon;

    #[Test]
    public function mdtExport_givenNoOriginHeaders_returnsForbidden(): void
    {
        // Arrange
        $dungeonRoute = $this->createDungeonRouteForMdtSupportedDungeon();

        try {
            // Act
            $response = $this->get($this->mdtExportUrl($dungeonRoute));

            // Assert
            $response->assertForbidden();
        } finally {
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function mdtExport_givenCrossSiteFetchMetadata_returnsForbidden(): void
    {
        // Arrange
        $dungeonRoute = $this->createDungeonRouteForMdtSupportedDungeon();

        try {
            // Act
            $response = $this->get($this->mdtExportUrl($dungeonRoute), ['Sec-Fetch-Site' => 'cross-site']);

            // Assert
            $response->assertForbidden();
        } finally {
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function mdtExport_givenRefererOfAnotherHost_returnsForbidden(): void
    {
        // Arrange
        $dungeonRoute = $this->createDungeonRouteForMdtSupportedDungeon();

        try {
            // Act
            $response = $this->get($this->mdtExportUrl($dungeonRoute), ['Referer' => 'https://scraper.example.com/keystone']);

            // Assert
            $response->assertForbidden();
        } finally {
            $dungeonRoute->delete();
        }
    }

    /**
     * Fetch metadata is authoritative when present: a sibling origin (or anything else that isn't
     * our own page) stays out even when it presents a Referer of our host.
     */
    #[Test]
    public function mdtExport_givenSameSiteFetchMetadataAndMatchingReferer_returnsForbidden(): void
    {
        // Arrange
        $dungeonRoute = $this->createDungeonRouteForMdtSupportedDungeon();

        try {
            // Act
            $response = $this->get($this->mdtExportUrl($dungeonRoute), [
                'Sec-Fetch-Site' => 'same-site',
                'Referer'        => 'http://localhost/embed/' . $dungeonRoute->public_key,
            ]);

            // Assert
            $response->assertForbidden();
        } finally {
            $dungeonRoute->delete();
        }
    }

    /**
     * An embed page rendered inside a third-party iframe is still served from our own origin, so
     * its XHR carries same-origin fetch metadata - this is the case that must keep working.
     */
    #[Test]
    public function mdtExport_givenSameOriginFetchMetadata_returnsOk(): void
    {
        // Arrange
        $dungeonRoute = $this->createDungeonRouteForMdtSupportedDungeon();

        try {
            // Act
            $response = $this->get($this->mdtExportUrl($dungeonRoute), ['Sec-Fetch-Site' => 'same-origin']);

            // Assert
            $response->assertOk();
            $response->assertJsonStructure(['mdt_string']);
        } finally {
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function mdtExport_givenRefererOfOurOwnHostWithoutFetchMetadata_returnsOk(): void
    {
        // Arrange
        $dungeonRoute = $this->createDungeonRouteForMdtSupportedDungeon();

        try {
            // Act
            $response = $this->get($this->mdtExportUrl($dungeonRoute), ['Referer' => 'http://localhost/embed/' . $dungeonRoute->public_key]);

            // Assert
            $response->assertOk();
            $response->assertJsonStructure(['mdt_string']);
        } finally {
            $dungeonRoute->delete();
        }
    }

    /**
     * Mints the signed url exactly as MapContextDungeonRoute does at page render.
     */
    private function mdtExportUrl(DungeonRoute $dungeonRoute): string
    {
        return URL::temporarySignedRoute(
            'api.dungeonroute.mdtexport',
            now()->addHours(config('keystoneguru.mdt.export_url_expiry_hours')),
            [
                'dungeonRoute' => $dungeonRoute,
                'useCache'     => 1,
            ],
            absolute: false,
        );
    }

    private function createDungeonRouteForMdtSupportedDungeon(): DungeonRoute
    {
        [$dungeon, $mappingVersion] = $this->findDungeon(
            challengeMode: true,
            resolve: static fn(Dungeon $dungeon) => $dungeon->mdt_supported ? true : null,
        );

        return DungeonRoute::factory()->create([
            'expires_at'         => null,
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $mappingVersion->id,
        ]);
    }
}
