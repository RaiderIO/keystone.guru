<?php

namespace Tests\Feature\App\Console\Commands\MDT;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * Guards that mdt:syncaddonversions --refresh authenticates its GitHub calls when a token is configured.
 * Unauthenticated requests are rate limited per IP, so a shared dev/CI egress address makes the refresh
 * fail with a HTTP 403 that reads like a permissions problem instead of a rate limit.
 */
#[Group('MDT')]
#[Group('MDTAddonVersion')]
final class SyncAddonVersionsTest extends PublicTestCase
{
    private const DATA_PATH = 'data/mdt/addon_versions.json';

    #[Test]
    public function refresh_givenConfiguredToken_sendsAuthorizationHeader(): void
    {
        // Arrange
        $originalJson = File::get(database_path(self::DATA_PATH));
        config(['github.connections.main.token' => 'test-token']);
        $this->fakeSingleReleasePage();

        try {
            // Act
            $this->artisan('mdt:syncaddonversions', ['--refresh' => true])->assertSuccessful();

            // Assert
            Http::assertSent(static fn(Request $request) => $request->hasHeader('Authorization', 'Bearer test-token'));
        } finally {
            File::put(database_path(self::DATA_PATH), $originalJson);
        }
    }

    #[Test]
    public function refresh_givenNoConfiguredToken_sendsNoAuthorizationHeader(): void
    {
        // Arrange
        $originalJson = File::get(database_path(self::DATA_PATH));
        config(['github.connections.main.token' => null]);
        $this->fakeSingleReleasePage();

        try {
            // Act
            $this->artisan('mdt:syncaddonversions', ['--refresh' => true])->assertSuccessful();

            // Assert
            Http::assertSent(static fn(Request $request) => !$request->hasHeader('Authorization'));
        } finally {
            File::put(database_path(self::DATA_PATH), $originalJson);
        }
    }

    /**
     * Fakes the GitHub releases endpoint with a single already-known release, so the command's upsert into
     * mdt_addon_versions is a no-op against the persistent seeded database.
     */
    private function fakeSingleReleasePage(): void
    {
        $page = 0;

        Http::fake([
            'api.github.com/repos/nnoggie/MythicDungeonTools/releases*' => static function () use (&$page) {
                return Http::response(++$page === 1
                    ? [['tag_name' => '6.2.2', 'published_at' => '2026-08-14T00:01:50Z']]
                    : []);
            },
        ]);
    }
}
