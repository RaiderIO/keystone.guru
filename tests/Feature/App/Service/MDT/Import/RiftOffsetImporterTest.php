<?php

namespace Tests\Feature\App\Service\MDT\Import;

use App\Models\Dungeon;
use App\Service\MDT\Import\RiftOffsetImporter;
use App\Service\MDT\Models\ImportStringRiftOffsets;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('MDT')]
#[Group('RiftOffsetImporter')]
final class RiftOffsetImporterTest extends PublicTestCase
{
    /**
     * Guards #3915: an MDT import string's rift offsets reference a fixed set of Awakened Obelisk
     * NPC ids. When the current mapping version's dungeon has the Obelisk map icons placed but no
     * matching Enemy row for one of those NPC ids (real seeded case: The Underrot's current mapping
     * version, id 158), the underlying Enemy::where(...)->firstOrFail() must not surface an uncaught
     * ModelNotFoundException - it should be recorded as a warning and that one skip omitted instead.
     */
    #[Test]
    public function parseRiftOffsets_givenRiftEnemyNotInCurrentMappingVersion_addsWarningInsteadOfThrowing(): void
    {
        // Arrange
        $dungeon        = Dungeon::where('key', 'theunderrot')->firstOrFail();
        $mappingVersion = $dungeon->getCurrentMappingVersion();

        $importStringRiftOffsets = new ImportStringRiftOffsets(
            warnings:       new Collection(),
            dungeon:        $dungeon,
            mappingVersion: $mappingVersion,
            seasonalIndex:  null,
            riftOffsets:    [
                1 => [
                    // Brutal Awakened Obelisk NPC id - not present as an Enemy on this mapping version
                    161124 => ['x' => 50.0, 'y' => 50.0],
                ],
            ],
            week: 1,
        );

        /** @var RiftOffsetImporter $importer */
        $importer = app(RiftOffsetImporter::class);

        // Act
        $result = $importer->parseRiftOffsets($importStringRiftOffsets);

        // Assert - no ModelNotFoundException, a warning was recorded, and nothing was queued for import
        $this->assertCount(1, $result->getWarnings());
        $this->assertSame(
            __('services.mdt.io.import_string.unable_to_find_awakened_obelisk_enemy'),
            $result->getWarnings()->first()->getMessage(),
        );
        $this->assertCount(0, $result->getMapIcons());
        $this->assertCount(0, $result->getPaths());
    }
}
