<?php

namespace Tests\Feature\Database\Migrations;

use App\Models\GameServerRegion;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * Guards the migration that stops users.game_server_region_id defaulting to -1 - see the
 * migration's own docblock and #4502.
 *
 * Everything here goes through the query builder on the migration's own connection rather than
 * through User/its factory: the model pins itself to the mysql connection (#4498), so an Eloquent
 * fixture would land in a different schema than the one the migration alters. The migration runs
 * DDL, which implicitly commits in MySQL, so the fixtures are cleaned up explicitly instead of by
 * rolling a transaction back.
 */
#[Group('Auth')]
final class DefaultUsersGameServerRegionIdToTheDefaultRegionTest extends PublicTestCase
{
    private const MIGRATION = 'migrations/2026_09_05_120000_default_users_game_server_region_id_to_the_default_region.php';

    #[Test]
    public function up_givenDanglingRegionId_repointsItToTheDefaultRegion(): void
    {
        // Arrange
        $userId = $this->createUser(['game_server_region_id' => -1]);

        try {
            // Act
            $migration = require database_path(self::MIGRATION);
            $migration->up();

            // Assert
            $this->assertSame(
                GameServerRegion::ALL[GameServerRegion::DEFAULT_REGION],
                (int)DB::table('users')->where('id', $userId)->value('game_server_region_id'),
                'The dangling region id should have been repointed at the default region.',
            );
        } finally {
            DB::table('users')->where('id', $userId)->delete();
        }
    }

    /**
     * The point of the migration: no insert path can produce a dangling region by omitting the
     * column, which is what LaratrustSeeder, the user factory and the OAuth login controllers all do.
     */
    #[Test]
    public function insert_givenNoGameServerRegionId_pointsAtAnExistingGameServerRegion(): void
    {
        // Arrange
        // Act
        $userId = $this->createUser();

        try {
            $regionId = (int)DB::table('users')->where('id', $userId)->value('game_server_region_id');

            // Assert
            $this->assertSame(
                GameServerRegion::ALL[GameServerRegion::DEFAULT_REGION],
                $regionId,
                'A user inserted without a region should fall back to the default region.',
            );
            $this->assertTrue(
                DB::table('game_server_regions')->where('id', $regionId)->exists(),
                sprintf('The column default (%d) is not an existing game server region', $regionId),
            );
        } finally {
            DB::table('users')->where('id', $userId)->delete();
        }
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function createUser(array $attributes = []): int
    {
        return DB::table('users')->insertGetId($attributes + [
            'public_key'       => sprintf('4502_%s', uniqid()),
            'name'             => 'Region default fixture',
            'email'            => sprintf('4502_%s@example.com', uniqid()),
            'echo_color'       => '#ffffff',
            'changed_username' => 0,
            'password'         => '',
        ]);
    }
}
