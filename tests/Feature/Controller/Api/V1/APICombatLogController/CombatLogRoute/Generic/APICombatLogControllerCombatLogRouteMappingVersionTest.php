<?php

namespace Tests\Feature\Controller\Api\V1\APICombatLogController\CombatLogRoute\Generic;

use App\Models\DungeonKey;
use App\Models\GameVersion\GameVersion;
use App\Models\Mapping\MappingVersion;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Controller\Api\V1\APICombatLogController\CombatLogRoute\APICombatLogControllerCombatLogRouteTestBase;

/**
 * Covers settings.mappingVersion itself rather than any one dungeon's route. Every other fixture pins a version so
 * that an MDT import cannot silently re-baseline it; the resolution behind that pin is asserted here, once, instead
 * of being duplicated per mapping version.
 */
#[Group('Controller')]
#[Group('API')]
#[Group('APICombatLog')]
#[Group('CombatLogRoute')]
#[Group('CombatLogRouteGeneric')]
#[Group('CombatLogRouteMappingVersion')]
class APICombatLogControllerCombatLogRouteMappingVersionTest extends APICombatLogControllerCombatLogRouteTestBase
{
    private const FIXTURE = 'BFA/midnight_s2_kings_rest';

    protected function getDungeonKey(): string
    {
        return DungeonKey::KINGS_REST->value;
    }

    /**
     * Regenerating an existing route posts that route's own stored mapping version back to this endpoint
     * (CombatLogRouteDungeonRouteService::regenerate()), so resolving a historical version is a production path and
     * not merely a test convenience.
     */
    #[Test]
    public function create_givenHistoricalMappingVersion_shouldCreateRouteOnThatMappingVersion(): void
    {
        // Arrange
        $postBody       = $this->getJsonData(self::FIXTURE, self::FIXTURES_ROOT_DIR);
        $pinnedVersion  = $postBody['settings']['mappingVersion'];
        $currentVersion = $this->getCurrentMappingVersion()->version;

        $this->assertNotSame(
            $currentVersion,
            $pinnedVersion,
            sprintf('Fixture %s no longer pins a historical mapping version - re-pin it to an older one', self::FIXTURE),
        );

        // Act
        $response = $this->post(route('api.v1.combatlog.route.store'), $postBody);

        // Assert
        $response->assertCreated();

        $responseArr = json_decode($response->content(), true);

        try {
            $this->assertSame($pinnedVersion, $responseArr['data']['mappingVersion']);
            $this->assertSame($this->dungeon->id, $responseArr['data']['dungeonId']);
        } finally {
            $this->deleteDungeonRoute($responseArr);
        }
    }

    #[Test]
    public function create_givenNoMappingVersion_shouldCreateRouteOnCurrentMappingVersion(): void
    {
        // Arrange
        $postBody = $this->getJsonData(self::FIXTURE, self::FIXTURES_ROOT_DIR);
        unset($postBody['settings']['mappingVersion']);

        // Act
        $response = $this->post(route('api.v1.combatlog.route.store'), $postBody);

        // Assert
        $response->assertCreated();

        $responseArr = json_decode($response->content(), true);

        try {
            $this->assertSame($this->getCurrentMappingVersion()->version, $responseArr['data']['mappingVersion']);
            $this->assertSame($this->dungeon->id, $responseArr['data']['dungeonId']);
        } finally {
            $this->deleteDungeonRoute($responseArr);
        }
    }

    /**
     * Raider.IO posts mapping versions we may since have dropped, so an unresolvable one must still yield a route
     * rather than a 4xx. The suite itself must never take this fallback - validateMappingVersion() enforces that.
     */
    #[Test]
    public function create_givenUnknownMappingVersion_shouldFallBackToCurrentMappingVersion(): void
    {
        // Arrange
        $postBody                               = $this->getJsonData(self::FIXTURE, self::FIXTURES_ROOT_DIR);
        $postBody['settings']['mappingVersion'] = 9999;

        // Act
        $response = $this->post(route('api.v1.combatlog.route.store'), $postBody);

        // Assert
        $response->assertCreated();

        $responseArr = json_decode($response->content(), true);

        try {
            $this->assertSame($this->getCurrentMappingVersion()->version, $responseArr['data']['mappingVersion']);
            $this->assertSame($this->dungeon->id, $responseArr['data']['dungeonId']);
        } finally {
            $this->deleteDungeonRoute($responseArr);
        }
    }

    private function getCurrentMappingVersion(): MappingVersion
    {
        return $this->dungeon->getCurrentMappingVersionForGameVersion(
            GameVersion::firstWhere('key', GameVersion::GAME_VERSION_RETAIL),
        );
    }
}
