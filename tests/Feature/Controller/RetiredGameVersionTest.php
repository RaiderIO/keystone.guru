<?php

namespace Tests\Feature\Controller;

use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\GameVersion\GameVersion;
use App\Models\Mapping\MappingVersion;
use App\Models\PublishedState;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('GameVersion')]
final class RetiredGameVersionTest extends PublicTestCase
{
    #[Test]
    #[DataProvider('retiredGameVersionKeyProvider')]
    public function update_givenRetiredGameVersion_redirectsToTheRetailToggle(string $retiredGameVersionKey): void
    {
        // Arrange
        $url = sprintf('/gameversion/%s', $retiredGameVersionKey);

        // Act
        $response = $this->get($url);

        // Assert
        $response->assertStatus(301);
        $response->assertRedirect(route('gameversion.update', ['gameVersion' => GameVersion::GAME_VERSION_RETAIL]));
    }

    #[Test]
    #[DataProvider('retiredGameVersionKeyProvider')]
    public function explore_givenRetiredGameVersionUrlForADungeon_redirectsToTheRetailUrlKeepingTheQueryString(
        string $retiredGameVersionKey,
    ): void {
        // Arrange
        /** @var Dungeon $dungeon */
        $dungeon = Dungeon::query()->where('key', 'courtofstars')->firstOrFail();
        $url     = sprintf('/explore/%s/%s?embedStyle=compact', $retiredGameVersionKey, $dungeon->slug);

        // Act
        $response = $this->get($url);

        // Assert
        $response->assertStatus(301);
        $response->assertRedirect(sprintf('%s?embedStyle=compact', route('dungeon.explore.gameversion.view', [
            'gameVersion' => GameVersion::GAME_VERSION_RETAIL,
            'dungeon'     => $dungeon,
        ])));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function retiredGameVersionKeyProvider(): array
    {
        return [
            'wrath'        => [GameVersion::GAME_VERSION_WRATH],
            'cata'         => [GameVersion::GAME_VERSION_CATA],
            'legion remix' => [GameVersion::GAME_VERSION_LEGION_REMIX],
        ];
    }

    #[Test]
    public function discover_givenRetiredGameVersionApiUrl_redirectsToTheRetailApiUrl(): void
    {
        // Arrange
        $url = sprintf('/api/v1/routes/%s/popular', GameVersion::GAME_VERSION_LEGION_REMIX);

        // Act
        $response = $this->get($url);

        // Assert
        $response->assertStatus(301);
        $response->assertRedirect(route('api.v1.discover.popular', ['gameVersion' => GameVersion::GAME_VERSION_RETAIL]));
    }

    #[Test]
    public function search_givenRetiredGameVersionPost_redirectsKeepingTheMethod(): void
    {
        // Arrange
        /** @var Dungeon $dungeon */
        $dungeon = Dungeon::query()->where('key', 'pitofsaron')->firstOrFail();
        $url     = sprintf('/ajax/dungeonroute/search/%s/%s', GameVersion::GAME_VERSION_WRATH, $dungeon->slug);

        // Act
        $response = $this->withoutMiddleware(ValidateCsrfToken::class)
            ->post($url);

        // Assert
        $response->assertStatus(308);
        $response->assertRedirectContains(sprintf('/ajax/dungeonroute/search/%s/', GameVersion::GAME_VERSION_RETAIL));
    }

    #[Test]
    public function explore_givenActiveGameVersion_isNotRedirected(): void
    {
        // Arrange
        $url = route('dungeon.explore.gameversion', ['gameVersion' => GameVersion::GAME_VERSION_RETAIL]);

        // Act
        $response = $this->get($url);

        // Assert
        $this->assertNotContains($response->getStatusCode(), [301, 308]);
    }

    #[Test]
    public function header_givenAnyPage_listsNoRetiredGameVersion(): void
    {
        // Arrange
        $activeGameVersions = GameVersion::active()->get();

        // Act
        $response = $this->get('/');

        // Assert
        $response->assertOk();
        $html = $response->getContent();
        foreach (array_keys(GameVersion::RETIRED_INTO) as $retiredGameVersionKey) {
            $this->assertNotContains($retiredGameVersionKey, $activeGameVersions->pluck('key'));
            $this->assertStringNotContainsString(
                route('gameversion.update', ['gameVersion' => $retiredGameVersionKey]),
                $html,
            );
        }

        $this->assertStringContainsString(
            route('gameversion.update', ['gameVersion' => GameVersion::GAME_VERSION_RETAIL]),
            $html,
        );
    }

    #[Test]
    #[DataProvider('formerlyRetiredDungeonKeyProvider')]
    public function dungeon_givenFormerlyRetiredDungeon_hasOnlyRetailMappingVersions(string $dungeonKey): void
    {
        // Arrange
        /** @var Dungeon $dungeon */
        $dungeon = Dungeon::query()->where('key', $dungeonKey)->firstOrFail();

        // Act
        $gameVersionIds = $dungeon->mappingVersions()->pluck('game_version_id')->unique()->values()->all();

        // Assert
        $this->assertSame([GameVersion::ALL[GameVersion::GAME_VERSION_RETAIL]], $gameVersionIds);
    }

    #[Test]
    #[DataProvider('formerlyRetiredDungeonKeyProvider')]
    public function select_givenRetailExploreSelector_listsFormerlyRetiredDungeons(string $dungeonKey): void
    {
        // Arrange
        /** @var Dungeon $dungeon */
        $dungeon = Dungeon::query()->where('key', $dungeonKey)->firstOrFail();

        // Act
        $response = $this->get(route('dungeon.explore.gameversion.select', [
            'gameVersion' => GameVersion::GAME_VERSION_RETAIL,
        ]));

        // Assert
        $response->assertOk();
        $this->assertStringContainsString(route('dungeon.explore.gameversion.view', [
            'gameVersion' => GameVersion::GAME_VERSION_RETAIL,
            'dungeon'     => $dungeon,
        ]), $response->getContent());
    }

    /**
     * @return array<string, array{string}>
     */
    public static function formerlyRetiredDungeonKeyProvider(): array
    {
        return [
            'wrath: ulduar'                   => ['ulduar'],
            'wrath: pit of saron'             => ['pitofsaron'],
            'cata: firelands'                 => ['firelands'],
            'legion: court of stars'          => ['courtofstars'],
            'legion: seat of the triumvirate' => ['theseatofthetriumvirate'],
        ];
    }

    #[Test]
    #[DataProvider('currentMappingVersionProvider')]
    public function getCurrentMappingVersionForGameVersion_givenDungeonWithRetiredAndRetailVersions_returnsTheAgreedVersion(
        string $dungeonKey,
        int    $expectedMappingVersionId,
    ): void {
        // Arrange
        /** @var Dungeon $dungeon */
        $dungeon = Dungeon::query()->where('key', $dungeonKey)->firstOrFail();
        $retail  = GameVersion::getDefaultGameVersion();

        // Act
        $currentMappingVersion = $dungeon->getCurrentMappingVersionForGameVersion($retail);

        // Assert
        $this->assertSame($expectedMappingVersionId, $currentMappingVersion?->id);
    }

    /**
     * Legion Remix supersedes Retail, except for Seat of the Triumvirate, which is in the current retail
     * season; Pit of Saron keeps its newest Retail version.
     *
     * @return array<string, array{string, int}>
     */
    public static function currentMappingVersionProvider(): array
    {
        return [
            'court of stars: remix'           => ['courtofstars', 631],
            'neltharions lair: remix'         => ['neltharionslair', 636],
            'seat of the triumvirate: retail' => ['theseatofthetriumvirate', 798],
            'pit of saron: retail'            => ['pitofsaron', 797],
        ];
    }

    #[Test]
    #[DataProvider('formerlyRetiredMappingVersionProvider')]
    public function view_givenRouteOnAFormerlyRetiredMappingVersion_rendersTheRoute(int $mappingVersionId): void
    {
        // Arrange
        $dungeonRoute = $this->createPublishedRoute($mappingVersionId);

        try {
            // Act
            $response = $this->followingRedirects()->get(route('dungeonroute.view', [
                'dungeon'      => $dungeonRoute->dungeon,
                'dungeonroute' => $dungeonRoute,
                'title'        => $dungeonRoute->getTitleSlug(),
            ]));

            // Assert
            $response->assertOk();
        } finally {
            $dungeonRoute->delete();
        }
    }

    /**
     * @return array<string, array{int}>
     */
    public static function formerlyRetiredMappingVersionProvider(): array
    {
        return [
            'wrath: pit of saron v0'                   => [49],
            'cata: firelands'                          => [423],
            'legion remix: court of stars'             => [631],
            'legion remix: seat of the triumvirate v0' => [637],
        ];
    }

    #[Test]
    #[DataProvider('formerlyRetiredMdtSupportedMappingVersionProvider')]
    public function mdtExport_givenRouteOnAFormerlyRetiredMappingVersion_returnsMdtString(int $mappingVersionId): void
    {
        // Arrange
        $dungeonRoute = $this->createPublishedRoute($mappingVersionId);

        try {
            // Act
            $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])->get(URL::temporarySignedRoute(
                'api.dungeonroute.mdtexport',
                now()->addHours(config('keystoneguru.mdt.export_url_expiry_hours')),
                ['dungeonRoute' => $dungeonRoute, 'useCache' => 0],
                absolute: false,
            ));

            // Assert
            $response->assertSuccessful();
            $this->assertNotEmpty($response->json('mdt_string'));
        } finally {
            $dungeonRoute->delete();
        }
    }

    /**
     * Firelands and Dragon Soul, the only Cataclysm content, are raids that MDT does not support.
     *
     * @return array<string, array{int}>
     */
    public static function formerlyRetiredMdtSupportedMappingVersionProvider(): array
    {
        return [
            'wrath: pit of saron v0'                   => [49],
            'legion remix: court of stars'             => [631],
            'legion remix: seat of the triumvirate v0' => [637],
        ];
    }

    private function createPublishedRoute(int $mappingVersionId): DungeonRoute
    {
        /** @var MappingVersion $mappingVersion */
        $mappingVersion = MappingVersion::query()->findOrFail($mappingVersionId);

        return DungeonRoute::factory()->create([
            'dungeon_id'         => $mappingVersion->dungeon_id,
            'mapping_version_id' => $mappingVersion->id,
            'expires_at'         => null,
            'published_state_id' => PublishedState::ALL[PublishedState::WORLD],
        ]);
    }
}
