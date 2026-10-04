<?php

namespace Tests\Feature\Database\Seeders;

use App\Models\GameVersion\GameVersion;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\GameVersionsSeeder;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('GameVersion')]
final class GameVersionsSeederTest extends PublicTestCase
{
    #[Test]
    public function run_givenEveryGameVersion_insertsEachWithItsOwnDisplayOrder(): void
    {
        // Arrange
        $tempTable = DatabaseSeeder::getTempTableName(GameVersion::class);
        DB::statement(sprintf('DROP TABLE IF EXISTS %s;', $tempTable));
        DB::statement(sprintf('CREATE TABLE %s LIKE %s;', $tempTable, (new GameVersion())->getTable()));

        try {
            // Act
            (new GameVersionsSeeder())->run();

            // Assert
            /** @var array<string, int> $displayOrderByKey */
            $displayOrderByKey = DB::table($tempTable)->pluck('display_order', 'key')->all();

            $this->assertCount(count(GameVersion::ALL), $displayOrderByKey);
            $this->assertSame(3, $displayOrderByKey[GameVersion::GAME_VERSION_TBC]);
            $this->assertSame(4, $displayOrderByKey[GameVersion::GAME_VERSION_SOD]);
            $this->assertSame(
                count($displayOrderByKey),
                count(array_unique($displayOrderByKey)),
                'Every game version needs its own position in the selectors',
            );
        } finally {
            DB::statement(sprintf('DROP TABLE IF EXISTS %s;', $tempTable));
        }
    }

    #[Test]
    public function run_givenForever_insertsItActive(): void
    {
        // Arrange
        $tempTable = DatabaseSeeder::getTempTableName(GameVersion::class);
        DB::statement(sprintf('DROP TABLE IF EXISTS %s;', $tempTable));
        DB::statement(sprintf('CREATE TABLE %s LIKE %s;', $tempTable, (new GameVersion())->getTable()));

        try {
            // Act
            (new GameVersionsSeeder())->run();

            // Assert
            $this->assertTrue((bool)DB::table($tempTable)->where('key', GameVersion::GAME_VERSION_FOREVER)->value('active'));
        } finally {
            DB::statement(sprintf('DROP TABLE IF EXISTS %s;', $tempTable));
        }
    }
}
