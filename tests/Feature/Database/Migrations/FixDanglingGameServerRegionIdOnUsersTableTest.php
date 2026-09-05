<?php

namespace Tests\Feature\Database\Migrations;

use App\Models\GameServerRegion;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * Guards the backfill that points users.game_server_region_id = -1 (the register form's old
 * placeholder value) at the default region - see the migration's own docblock and #4003.
 *
 * The migration sweeps every -1 row, not just the fixture, and its down() is a deliberate no-op,
 * so every call runs inside a transaction that is always rolled back; that disposes of the
 * fixture user too.
 */
#[Group('Auth')]
final class FixDanglingGameServerRegionIdOnUsersTableTest extends PublicTestCase
{
    private const MIGRATION = 'migrations/2026_08_13_210000_fix_dangling_game_server_region_id_on_users_table.php';

    private const ORIGINAL_UPDATED_AT = '2020-01-01 00:00:00';

    #[Test]
    public function up_givenDanglingRegionId_repointsItToTheDefaultRegionWithoutTouchingUpdatedAt(): void
    {
        // Arrange
        // Every statement below - including the migration's table-wide sweep - has to be
        // transacted on the User model's own connection: it pins itself to mysql (#4498) while the
        // default connection under PHPUnit is phpunit, and a transaction opened on the other
        // connection rolls back nothing
        $connection = DB::connection((new User())->getConnectionName());
        $connection->beginTransaction();

        try {
            $user = User::factory()->create();
            // Straight through the query builder - Eloquent would overwrite updated_at itself, and
            // the -1 is no longer a value the model layer will accept
            User::query()->where('id', $user->id)->toBase()->update([
                'game_server_region_id' => -1,
                'updated_at'            => self::ORIGINAL_UPDATED_AT,
            ]);

            // Act
            $connection->flushQueryLog();
            $connection->enableQueryLog();

            try {
                $migration = require database_path(self::MIGRATION);
                $migration->up();

                $queryLog = $connection->getQueryLog();
            } finally {
                $connection->disableQueryLog();
            }

            // Assert
            $row = User::query()->where('id', $user->id)->toBase()->first();

            $this->assertSame(
                GameServerRegion::ALL[GameServerRegion::DEFAULT_REGION],
                (int)$row->game_server_region_id,
                'The dangling region id should have been repointed at the default region.',
            );
            $this->assertSame(
                self::ORIGINAL_UPDATED_AT,
                (string)$row->updated_at,
                'The backfill must not rewrite updated_at - it is irreversible and down() restores nothing.',
            );

            foreach ($queryLog as $query) {
                $this->assertStringNotContainsString(
                    'updated_at',
                    $query['query'],
                    sprintf('The backfill wrote updated_at: %s', $query['query']),
                );
            }
        } finally {
            $connection->rollBack();
        }
    }

    /**
     * The whole point of the repair: afterwards no user points at a region that does not exist.
     * Meaningful against a production-like database; on a freshly seeded one it simply confirms
     * the seeded users are consistent.
     */
    #[Test]
    public function users_givenSeededDatabase_allReferenceAnExistingGameServerRegion(): void
    {
        // Arrange
        // Act
        $dangling = User::query()
            ->whereNotNull('game_server_region_id')
            ->whereDoesntHave('gameServerRegion')
            ->count();

        // Assert
        $this->assertSame(0, $dangling, sprintf('%d users point at a game server region that does not exist', $dangling));
    }
}
