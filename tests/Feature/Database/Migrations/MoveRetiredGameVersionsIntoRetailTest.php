<?php

namespace Tests\Feature\Database\Migrations;

use App\Models\GameVersion\GameVersion;
use App\Models\PublishedState;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * The migration only moves rows, so each run is wrapped in a transaction that is always rolled back; that
 * also disposes of the fixtures.
 */
#[Group('GameVersion')]
final class MoveRetiredGameVersionsIntoRetailTest extends PublicTestCase
{
    private const string MIGRATION = 'migrations/2026_09_30_000000_move_retired_game_versions_into_retail.php';

    #[Test]
    #[DataProvider('retiredGameVersionKeyProvider')]
    public function up_givenUserAndCollectionOnARetiredGameVersion_movesThemToRetail(string $retiredGameVersionKey): void
    {
        // Arrange
        DB::beginTransaction();

        try {
            $user         = User::factory()->create(['game_version_id' => GameVersion::ALL[$retiredGameVersionKey]]);
            $collectionId = $this->createCollection($user, GameVersion::ALL[$retiredGameVersionKey]);

            // Act
            $migration = require database_path(self::MIGRATION);
            $migration->up();

            // Assert
            $retailGameVersionId = GameVersion::ALL[GameVersion::GAME_VERSION_RETAIL];
            $this->assertSame($retailGameVersionId, (int)DB::table('users')->where('id', $user->id)->value('game_version_id'));
            $this->assertSame(
                $retailGameVersionId,
                (int)DB::table('dungeon_route_collections')->where('id', $collectionId)->value('game_version_id'),
            );
        } finally {
            DB::rollBack();
        }
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
    public function up_givenUserAndCollectionOnAnActiveGameVersion_leavesThemAlone(): void
    {
        // Arrange
        $classicGameVersionId = GameVersion::ALL[GameVersion::GAME_VERSION_CLASSIC_ERA];

        DB::beginTransaction();

        try {
            $user         = User::factory()->create(['game_version_id' => $classicGameVersionId]);
            $collectionId = $this->createCollection($user, $classicGameVersionId);

            // Act
            $migration = require database_path(self::MIGRATION);
            $migration->up();

            // Assert
            $this->assertSame($classicGameVersionId, (int)DB::table('users')->where('id', $user->id)->value('game_version_id'));
            $this->assertSame(
                $classicGameVersionId,
                (int)DB::table('dungeon_route_collections')->where('id', $collectionId)->value('game_version_id'),
            );
        } finally {
            DB::rollBack();
        }
    }

    private function createCollection(User $user, int $gameVersionId): int
    {
        return DB::table('dungeon_route_collections')->insertGetId([
            'user_id'            => $user->id,
            'game_version_id'    => $gameVersionId,
            'public_key'         => Str::random(32),
            'published_state_id' => PublishedState::ALL[PublishedState::UNPUBLISHED],
            'name'               => 'Retired game version fixture',
        ]);
    }
}
