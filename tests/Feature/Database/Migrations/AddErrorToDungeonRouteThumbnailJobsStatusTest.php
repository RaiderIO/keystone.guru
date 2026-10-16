<?php

namespace Tests\Feature\Database\Migrations;

use App\Models\DungeonRoute\DungeonRouteThumbnailJob;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use Tests\TestCases\PublicTestCase;

/**
 * The migration alters the schema, which MySQL commits implicitly, so a transaction cannot undo it.
 * Each test starts from the migrated schema and always ends back on it.
 */
#[Group('Migrations')]
final class AddErrorToDungeonRouteThumbnailJobsStatusTest extends PublicTestCase
{
    private const MIGRATION = 'migrations/2026_10_16_000000_add_error_to_dungeon_route_thumbnail_jobs_status.php';

    #[Test]
    public function up_givenTheStatusColumnWithoutError_addsErrorAndKeepsTheColumnAttributes(): void
    {
        // Arrange
        $migration = require database_path(self::MIGRATION);

        try {
            $migration->down();
            $this->assertStringNotContainsString("'error'", $this->statusColumn()->Type);

            // Act
            $migration->up();

            // Assert
            $column = $this->statusColumn();
            $this->assertSame("enum('queued','completed','expired','error')", $column->Type);
            $this->assertSame('NO', $column->Null);
            $this->assertSame(DungeonRouteThumbnailJob::STATUS_QUEUED, $column->Default);
        } finally {
            $migration->up();
        }
    }

    #[Test]
    public function up_givenTheMigratedSchema_acceptsEveryThumbnailJobStatus(): void
    {
        // Arrange
        $statuses = collect(new ReflectionClass(DungeonRouteThumbnailJob::class)->getConstants())
            ->filter(static fn(mixed $value, string $name): bool => str_starts_with($name, 'STATUS_'))
            ->values();

        // Act
        $columnType = $this->statusColumn()->Type;

        // Assert
        $this->assertNotEmpty($statuses);
        foreach ($statuses as $status) {
            $this->assertStringContainsString(sprintf("'%s'", $status), $columnType);
        }
    }

    private function statusColumn(): object
    {
        return collect(DB::select('SHOW COLUMNS FROM `dungeon_route_thumbnail_jobs`'))
            ->firstOrFail(static fn(object $column): bool => $column->Field === 'status');
    }
}
