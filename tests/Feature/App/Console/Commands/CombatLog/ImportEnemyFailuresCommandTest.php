<?php

namespace Tests\Feature\App\Console\Commands\CombatLog;

use App\Models\CombatLog\CombatLogRouteEnemyFailure;
use App\Models\Dungeon;
use App\Models\Floor\Floor;
use App\Models\Mapping\MappingVersion;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\ProvidesDungeon;
use Tests\TestCases\PublicTestCase;

#[Group('CombatLog')]
#[Group('ImportEnemyFailuresCommand')]
final class ImportEnemyFailuresCommandTest extends PublicTestCase
{
    use ProvidesDungeon;

    private const string BASE_URL = 'https://ksg-test.example';

    private Dungeon $dungeon;

    private Floor $floor;

    private MappingVersion $mappingVersion;

    private string $credentialsFile;

    private string $postBodiesDir;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        [$this->dungeon, $this->mappingVersion] = $this->findDungeon(facadeEnabled: false);

        /** @var Floor $floor */
        $floor       = $this->dungeon->floors()->where('facade', 0)->firstOrFail();
        $this->floor = $floor;

        config(['keystoneguru.remote_hosts.production.base_url' => self::BASE_URL]);

        $this->credentialsFile = storage_path(sprintf('framework/testing/import-enemy-failures-%s.txt', uniqid()));
        $this->postBodiesDir   = storage_path(sprintf('framework/testing/import-enemy-failures-bodies-%s', uniqid()));
        File::ensureDirectoryExists(dirname($this->credentialsFile));
        File::put($this->credentialsFile, "admin@example.com:secret\n");
    }

    #[\Override]
    protected function tearDown(): void
    {
        try {
            File::delete($this->credentialsFile);
            File::deleteDirectory($this->postBodiesDir);
            CombatLogRouteEnemyFailure::query()->where('dungeon_id', $this->dungeon->id)->delete();
        } finally {
            parent::tearDown();
        }
    }

    #[Test]
    public function handle_givenTwoPages_replacesLocalRowsAndStopsWhenHasMoreIsFalse(): void
    {
        // Arrange — a stale local row that must disappear, and two remote pages
        $stale = $this->createLocalFailure(['npc_id' => 1]);

        Http::fake([
            self::BASE_URL . '/api/v1/combatlog/enemy-failures/*' => function (Request $request) {
                $afterId = (int)($request->data()['after_id'] ?? 0);

                return $afterId === 0
                    ? Http::response(self::page([$this->remoteRow(10, 501, 'KEY-A'), $this->remoteRow(11, 502, 'KEY-B')], 11, true))
                    : Http::response(self::page([$this->remoteRow(12, 503, 'KEY-A')], null, false));
            },
        ]);

        // Act
        $this->artisan('combatlog:importenemyfailures', [
            'dungeon'            => $this->dungeon->key,
            '--credentials-file' => $this->credentialsFile,
        ])->assertSuccessful();

        // Assert — exactly the three remote rows, the stale one gone, both pages requested with basic auth
        $failures = CombatLogRouteEnemyFailure::query()->where('dungeon_id', $this->dungeon->id)->orderBy('id')->get();
        $this->assertCount(3, $failures);
        $this->assertNull(CombatLogRouteEnemyFailure::find($stale->id));
        $this->assertSame([501, 502, 503], $failures->pluck('npc_id')->all());
        $this->assertSame($this->floor->id, $failures->first()->floor_id);

        Http::assertSentCount(2);
        Http::assertSent(static fn(Request $request) => $request->hasHeader('Authorization') && ($request->data()['after_id'] ?? null) === 11);
    }

    #[Test]
    public function handle_givenMissingCredentialsFile_returnsFailureWithoutTouchingLocalRows(): void
    {
        // Arrange
        $existing = $this->createLocalFailure();
        Http::fake();

        // Act
        $this->artisan('combatlog:importenemyfailures', [
            'dungeon'            => $this->dungeon->key,
            '--credentials-file' => $this->credentialsFile . '.missing',
        ])->assertFailed();

        // Assert
        $this->assertNotNull(CombatLogRouteEnemyFailure::find($existing->id));
        Http::assertNothingSent();
    }

    #[Test]
    public function handle_givenFirstPageFails_leavesLocalRowsUntouched(): void
    {
        // Arrange
        $existing = $this->createLocalFailure();
        Http::fake([
            self::BASE_URL . '/api/v1/combatlog/enemy-failures/*' => Http::response(['error' => 'Unauthenticated'], 401),
        ]);

        // Act
        $this->artisan('combatlog:importenemyfailures', [
            'dungeon'            => $this->dungeon->key,
            '--credentials-file' => $this->credentialsFile,
        ])->assertFailed();

        // Assert
        $this->assertNotNull(CombatLogRouteEnemyFailure::find($existing->id));
    }

    #[Test]
    public function handle_givenUnreachableHost_returnsFailureWithoutTouchingLocalRows(): void
    {
        // Arrange
        $existing = $this->createLocalFailure();
        Http::fake(static fn() => throw new ConnectionException('cURL error 6: Could not resolve host'));

        // Act
        $this->artisan('combatlog:importenemyfailures', [
            'dungeon'            => $this->dungeon->key,
            '--credentials-file' => $this->credentialsFile,
        ])->assertFailed();

        // Assert
        $this->assertNotNull(CombatLogRouteEnemyFailure::find($existing->id));
    }

    #[Test]
    public function handle_givenMappingVersionOption_replacesOnlyThatMappingVersionAndPassesItOn(): void
    {
        // Arrange — one local row in the targeted mapping version (replaced) and one in another (kept)
        $targeted = $this->createLocalFailure(['mapping_version_id' => $this->mappingVersion->id]);
        $other    = $this->createLocalFailure(['mapping_version_id' => PHP_INT_MAX]);

        Http::fake([
            self::BASE_URL . '/api/v1/combatlog/enemy-failures/*' => Http::response(self::page([$this->remoteRow(20, 601, null)], null, false)),
        ]);

        // Act
        $this->artisan('combatlog:importenemyfailures', [
            'dungeon'            => $this->dungeon->key,
            '--credentials-file' => $this->credentialsFile,
            '--mapping-version'  => $this->mappingVersion->id,
        ])->assertSuccessful();

        // Assert
        $this->assertNull(CombatLogRouteEnemyFailure::find($targeted->id));
        $this->assertNotNull(CombatLogRouteEnemyFailure::find($other->id));
        $this->assertSame(1, CombatLogRouteEnemyFailure::query()->where('dungeon_id', $this->dungeon->id)->where('npc_id', 601)->count());
        Http::assertSent(fn(Request $request) => ($request->data()['mapping_version_id'] ?? null) === $this->mappingVersion->id);
    }

    #[Test]
    public function handle_givenDownloadPostBodiesDir_writesOneFilePerDistinctRouteAndSkipsRoutesWithout(): void
    {
        // Arrange — two rows of route KEY-A, one of KEY-B (which has no stored body), one without a route
        Http::fake([
            self::BASE_URL . '/api/v1/combatlog/enemy-failures/*' => Http::response(self::page([
                $this->remoteRow(30, 701, 'KEY-A'),
                $this->remoteRow(31, 701, 'KEY-A'),
                $this->remoteRow(32, 702, 'KEY-B'),
                $this->remoteRow(33, 703, null),
            ], null, false)),
            self::BASE_URL . '/api/v1/combatlog/route/KEY-A/post-body' => Http::response('{"npcs":[]}', 200, ['Content-Type' => 'application/json']),
            self::BASE_URL . '/api/v1/combatlog/route/KEY-B/post-body' => Http::response(['error' => 'none'], 404),
        ]);

        // Act
        $this->artisan('combatlog:importenemyfailures', [
            'dungeon'                => $this->dungeon->key,
            '--credentials-file'     => $this->credentialsFile,
            '--download-post-bodies' => $this->postBodiesDir,
        ])->assertSuccessful();

        // Assert
        $this->assertSame('{"npcs":[]}', File::get($this->postBodiesDir . '/KEY-A.json'));
        $this->assertFalse(File::exists($this->postBodiesDir . '/KEY-B.json'));
        // 1 failures page + 2 post body requests (KEY-A once despite two rows, KEY-B once)
        Http::assertSentCount(3);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function createLocalFailure(array $attributes = []): CombatLogRouteEnemyFailure
    {
        return CombatLogRouteEnemyFailure::create(array_merge([
            'dungeon_id'         => $this->dungeon->id,
            'floor_id'           => $this->floor->id,
            'mapping_version_id' => $this->mappingVersion->id,
            'npc_id'             => null,
            'lat'                => -50.0,
            'lng'                => 100.0,
        ], $attributes));
    }

    /**
     * @return array<string, mixed>
     */
    private function remoteRow(int $id, int $npcId, ?string $publicKey): array
    {
        return [
            'id'                       => $id,
            'dungeon_id'               => 999999,
            'floor_id'                 => $this->floor->id,
            'mapping_version_id'       => $this->mappingVersion->id,
            'npc_id'                   => $npcId,
            'dungeon_route_id'         => $publicKey === null ? null : $id * 10,
            'dungeon_route_public_key' => $publicKey,
            'lat'                      => -12.5,
            'lng'                      => 34.25,
            'created_at'               => '2026-08-20T10:00:00+00:00',
        ];
    }

    /**
     * @param  array<int, array<string, mixed>> $rows
     * @return array<string, mixed>
     */
    private static function page(array $rows, ?int $nextAfterId, bool $hasMore): array
    {
        return [
            'data' => $rows,
            'meta' => [
                'count'         => count($rows),
                'next_after_id' => $nextAfterId,
                'has_more'      => $hasMore,
            ],
        ];
    }
}
