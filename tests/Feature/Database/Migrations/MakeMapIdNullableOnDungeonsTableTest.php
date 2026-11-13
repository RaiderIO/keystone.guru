<?php

namespace Tests\Feature\Database\Migrations;

use App\Models\Dungeon;
use App\Models\DungeonKey;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * The migration alters the schema, which MySQL commits implicitly, so a transaction cannot undo it.
 * Each test starts from the migrated schema and always ends back on it.
 */
#[Group('Migrations')]
#[Group('Dungeon')]
final class MakeMapIdNullableOnDungeonsTableTest extends PublicTestCase
{
    private const string MIGRATION = 'migrations/2026_11_13_000000_make_map_id_nullable_on_dungeons_table.php';

    private const string DEFAULTED_DUNGEON_KEY = DungeonKey::THE_DROWNED_CITY->value;
    private const string NEGATIVE_DUNGEON_KEY  = 'orgrimmarhorrificvision';

    #[Test]
    public function up_givenDefaultedAndNegativeMapIds_convertsThemToNullButKeepsEasternKingdoms(): void
    {
        // Arrange
        $migration = require database_path(self::MIGRATION);

        try {
            $migration->down();
            DB::table('dungeons')->where('key', self::NEGATIVE_DUNGEON_KEY)->update(['map_id' => -1]);
            $this->assertSame(0, $this->getMapId(self::DEFAULTED_DUNGEON_KEY));

            // Act
            $migration->up();

            // Assert
            $column = $this->mapIdColumn();
            $this->assertSame('YES', $column->Null);
            $this->assertNull($column->Default);
            $this->assertSame('MUL', $column->Key);
            $this->assertNull($this->getMapId(self::DEFAULTED_DUNGEON_KEY));
            $this->assertNull($this->getMapId(self::NEGATIVE_DUNGEON_KEY));
            $this->assertSame(0, $this->getMapId(DungeonKey::EASTERN_KINGDOMS->value));
            $this->assertSame(1, $this->getMapId(DungeonKey::KALIMDOR->value));
            $this->assertSame(
                0,
                DB::table('dungeons')->where('map_id', '<', 0)->count(),
            );
        } finally {
            $migration->up();
        }
    }

    #[Test]
    public function down_givenNullMapIds_restoresTheNotNullColumnDefaultingToZero(): void
    {
        // Arrange
        $migration = require database_path(self::MIGRATION);
        $this->assertNull($this->getMapId(self::DEFAULTED_DUNGEON_KEY));

        try {
            // Act
            $migration->down();

            // Assert
            $column = $this->mapIdColumn();
            $this->assertSame('NO', $column->Null);
            $this->assertSame('0', $column->Default);
            $this->assertSame(0, $this->getMapId(self::DEFAULTED_DUNGEON_KEY));
        } finally {
            $migration->up();
        }
    }

    #[Test]
    public function dungeonsSeederJson_givenTheMigratedSchema_onlyGivesEasternKingdomsMapIdZero(): void
    {
        // Arrange
        /** @var list<array{key: string, map_id: int|null}> $dungeons */
        $dungeons = json_decode(
            (string)file_get_contents(database_path('seeders/dungeondata/dungeons.json')),
            true,
        );

        // Act
        $mapIdsAtZeroOrBelow = collect($dungeons)
            ->filter(static fn(array $dungeon): bool => $dungeon['map_id'] !== null && $dungeon['map_id'] <= 0)
            ->pluck('map_id', 'key')
            ->all();

        // Assert
        $this->assertSame([DungeonKey::EASTERN_KINGDOMS->value => 0], $mapIdsAtZeroOrBelow);
        $defaultedDungeon = collect($dungeons)->firstWhere('key', self::DEFAULTED_DUNGEON_KEY);
        $this->assertIsArray($defaultedDungeon);
        $this->assertArrayHasKey('map_id', $defaultedDungeon);
        $this->assertNull($defaultedDungeon['map_id']);
    }

    private function getMapId(string $key): ?int
    {
        $mapId = Dungeon::query()->where('key', $key)->value('map_id');

        return $mapId === null ? null : (int)$mapId;
    }

    private function mapIdColumn(): object
    {
        return collect(DB::select('SHOW COLUMNS FROM `dungeons`'))
            ->firstOrFail(static fn(object $column): bool => $column->Field === 'map_id');
    }
}
