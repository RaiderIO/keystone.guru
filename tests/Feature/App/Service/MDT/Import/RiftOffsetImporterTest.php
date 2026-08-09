<?php

namespace Tests\Feature\App\Service\MDT\Import;

use App\Models\Dungeon;
use App\Models\Enemy;
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
    private const string DUNGEON_KEY   = 'theunderrot';
    private const int    BRUTAL_NPC_ID = 161124;

    /**
     * Guards #3915: an MDT import string's rift offsets reference a fixed set of Awakened Obelisk
     * NPC ids. The underlying Enemy::where(...)->firstOrFail() query requires a *packed*
     * (enemy_pack_id not null) row - when the current mapping version's dungeon has the Obelisk map
     * icons placed but no such row for one of those NPC ids (real seeded case: The Underrot's
     * current mapping version has an unpacked Enemy row for the Brutal obelisk NPC, not none at
     * all - packed rows only exist on older mapping versions per the seeder data), it must not
     * surface an uncaught ModelNotFoundException - it should be recorded as a warning and that one
     * skip omitted instead.
     */
    #[Test]
    public function parseRiftOffsets_givenRiftEnemyNotInCurrentMappingVersion_addsWarningInsteadOfThrowing(): void
    {
        // Arrange
        $dungeon        = Dungeon::where('key', self::DUNGEON_KEY)->firstOrFail();
        $mappingVersion = $dungeon->getCurrentMappingVersion();

        // Pin the test's premise explicitly, so a future data/query change that makes this NPC
        // resolvable again fails loudly here with the reason, instead of as a confusing
        // "warnings count 0 != 1" a few lines down.
        $this->assertSame(
            0,
            Enemy::where('npc_id', self::BRUTAL_NPC_ID)
                ->where('mapping_version_id', $mappingVersion->id)
                ->whereNotNull('enemy_pack_id')
                ->count(),
            sprintf(
                'Test premise no longer holds: NPC %d now has a packed Enemy row on %s\'s current mapping version (%d)',
                self::BRUTAL_NPC_ID,
                self::DUNGEON_KEY,
                $mappingVersion->id,
            ),
        );

        $importStringRiftOffsets = new ImportStringRiftOffsets(
            warnings:       new Collection(),
            dungeon:        $dungeon,
            mappingVersion: $mappingVersion,
            seasonalIndex:  null,
            riftOffsets:    [
                1 => [
                    self::BRUTAL_NPC_ID => ['x' => 50.0, 'y' => 50.0],
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
            __(
                'services.mdt.io.import_string.unable_to_find_awakened_obelisk_enemy',
                ['name' => __('mapicontypes.awakened_obelisk_brutal')],
            ),
            $result->getWarnings()->first()->getMessage(),
        );
        $this->assertCount(0, $result->getMapIcons());
        $this->assertCount(0, $result->getPaths());
    }

    /**
     * Guards a second, related failure mode found while fixing #3915: $npcId comes straight from
     * the user-supplied import string and isn't constrained to the 4 hardcoded obelisk ids, so an
     * arbitrary rift npc id must not hit an undefined array key on $npcIdToMapIconMapping (which
     * would otherwise throw an uncaught Error - the same failure class as #3915, just uncaught by
     * the ModelNotFoundException guard).
     */
    #[Test]
    public function parseRiftOffsets_givenUnrecognizedRiftNpcId_skipsWithoutThrowing(): void
    {
        // Arrange
        $dungeon        = Dungeon::where('key', self::DUNGEON_KEY)->firstOrFail();
        $mappingVersion = $dungeon->getCurrentMappingVersion();

        $importStringRiftOffsets = new ImportStringRiftOffsets(
            warnings:       new Collection(),
            dungeon:        $dungeon,
            mappingVersion: $mappingVersion,
            seasonalIndex:  null,
            riftOffsets:    [
                1 => [
                    // Not one of the 4 hardcoded Awakened Obelisk npc ids
                    1 => ['x' => 50.0, 'y' => 50.0],
                ],
            ],
            week: 1,
        );

        /** @var RiftOffsetImporter $importer */
        $importer = app(RiftOffsetImporter::class);

        // Act
        $result = $importer->parseRiftOffsets($importStringRiftOffsets);

        // Assert - no Error, silently skipped (not a recognized obelisk skip to warn about)
        $this->assertCount(0, $result->getWarnings());
        $this->assertCount(0, $result->getMapIcons());
        $this->assertCount(0, $result->getPaths());
    }
}
