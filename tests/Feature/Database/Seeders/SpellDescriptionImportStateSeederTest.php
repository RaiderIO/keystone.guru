<?php

namespace Tests\Feature\Database\Seeders;

use App\Models\Spell\SpellDescriptionImportState;
use Database\Seeders\SpellDescriptionImportStateSeeder;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * Guards that SpellDescriptionImportStateSeeder imports the committed
 * database/data/spell_description/import_state.json verbatim, so a freshly seeded database records a
 * last-imported build without anyone running the real import first (#4501). The seeder is idempotent
 * (upsert) and the table is already seeded, so the test first corrupts its rows inside a transaction that
 * is always rolled back - otherwise a seeder that writes nothing would pass.
 */
#[Group('SpellDescription')]
final class SpellDescriptionImportStateSeederTest extends PublicTestCase
{
    private const string DATA_PATH = 'data/spell_description/import_state.json';

    #[Test]
    public function run_givenCommittedJson_recordsAnImportForEveryGameVersion(): void
    {
        // Arrange
        /** @var array<string, array{product: string, build: string, importedAt: string}> $expected */
        $expected = json_decode(file_get_contents(database_path(self::DATA_PATH)), true);
        $this->assertNotEmpty($expected, 'The committed import state file must list at least one game version.');

        DB::beginTransaction();

        try {
            SpellDescriptionImportState::query()
                ->whereKey(array_map(intval(...), array_keys($expected)))
                ->update(['product' => 'stale', 'build' => '0.0.0.1', 'imported_at' => '2000-01-01 00:00:00']);

            // Act
            $this->seed(SpellDescriptionImportStateSeeder::class);

            // Assert
            foreach ($expected as $gameVersionId => $state) {
                /** @var SpellDescriptionImportState $actual */
                $actual = SpellDescriptionImportState::query()->findOrFail((int)$gameVersionId);

                $this->assertSame($state['product'], $actual->product);
                $this->assertSame($state['build'], $actual->build);
                $this->assertSame($state['importedAt'], $actual->imported_at->toDateTimeString());
            }
        } finally {
            DB::rollBack();
        }
    }
}
