<?php

namespace Tests\Feature\App\Service\MDT\Import;

use App\Models\Dungeon;
use App\Models\Enemy;
use App\Models\MapIcon;
use App\Models\MapIconType;
use App\Service\MDT\Import\RiftOffsetImporter;
use App\Service\MDT\Models\ImportStringRiftOffsets;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('MDT')]
#[Group('RiftOffsetImporter')]
final class RiftOffsetImporterTest extends PublicTestCase
{
    private const string DUNGEON_KEY_UNPACKED_ONLY = 'theunderrot';
    private const string DUNGEON_KEY_NO_ENEMY      = 'ataldazar';
    private const string DUNGEON_KEY_MULTI_FLOOR   = 'toldagor';
    private const int    BRUTAL_NPC_ID             = 161124;

    /**
     * Guards #3935: the underlying Enemy lookup used to require a *packed* (enemy_pack_id not
     * null) row, which several BFA dungeons' current mapping versions never carry for these NPC
     * ids (packed rows only exist on older mapping versions per the seeder data) - even though an
     * unpacked row for the same NPC, on the same floor as the Obelisk map icon, does exist. The
     * lookup must resolve that unpacked row instead of surfacing a ModelNotFoundException.
     */
    #[Test]
    public function parseRiftOffsets_givenRiftEnemyOnlyUnpackedOnCurrentMappingVersion_resolvesEnemyAndAddsMapIcon(): void
    {
        // Arrange
        $dungeon        = Dungeon::where('key', self::DUNGEON_KEY_UNPACKED_ONLY)->firstOrFail();
        $mappingVersion = $dungeon->getCurrentMappingVersion();

        // Pin the test's premise explicitly, so a future data/query change fails loudly here with
        // the reason, instead of as a confusing assertion failure a few lines down.
        $expectedEnemy = Enemy::where('npc_id', self::BRUTAL_NPC_ID)
            ->where('mapping_version_id', $mappingVersion->id)
            ->firstOrFail(); // throws if the seeded premise (an Enemy row exists at all) breaks
        $this->assertNull(
            $expectedEnemy->enemy_pack_id,
            sprintf(
                'Test premise no longer holds: NPC %d now has a packed Enemy row on %s\'s current mapping version (%d)',
                self::BRUTAL_NPC_ID,
                self::DUNGEON_KEY_UNPACKED_ONLY,
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

        // Assert - resolved successfully, on the enemy's (and thus the obelisk icon's) floor
        $this->assertCount(0, $result->getWarnings());
        $this->assertCount(1, $result->getMapIcons());
        $this->assertCount(1, $result->getPaths());
        $this->assertSame($expectedEnemy->floor_id, $result->getMapIcons()->first()['floor_id']);
        $this->assertSame($expectedEnemy->floor_id, $result->getPaths()->first()['floor_id']);
    }

    /**
     * Guards the multi-floor disambiguation this NPC lookup relies on: the same npc_id can be
     * placed on more than one floor of the same dungeon (real seeded case: Tol Dagor). Anchoring
     * the lookup to the Obelisk map icon's own floor - rather than an arbitrary match across every
     * floor of the dungeon - must resolve the enemy on that same floor, not a different one.
     */
    #[Test]
    public function parseRiftOffsets_givenRiftNpcPlacedOnMultipleFloors_resolvesEnemyOnObeliskIconFloor(): void
    {
        // Arrange
        $dungeon        = Dungeon::where('key', self::DUNGEON_KEY_MULTI_FLOOR)->firstOrFail();
        $mappingVersion = $dungeon->getCurrentMappingVersion();
        $floorIds       = $dungeon->floors->pluck('id');

        // Mirror parseRiftOffsets()'s own seasonal_index predicate: with no import string season
        // set, this dungeon has more than one Brutal obelisk icon on the current mapping version's
        // floors, and only this predicate (not row order) picks the one the importer would.
        $obeliskMapIcon = MapIcon::where(
            'map_icon_type_id',
            MapIconType::ALL[MapIconType::MAP_ICON_TYPE_AWAKENED_OBELISK_BRUTAL],
        )
            ->whereIn('floor_id', $floorIds)
            ->where(static function (Builder $query) {
                $query->whereNull('seasonal_index')->orWhere('seasonal_index', 1);
            })
            ->firstOrFail();

        // Pin the test's premise explicitly: this NPC must actually be placed on more than one
        // floor of this dungeon, otherwise this test isn't exercising the disambiguation at all.
        $distinctFloors = Enemy::where('npc_id', self::BRUTAL_NPC_ID)
            ->where('mapping_version_id', $mappingVersion->id)
            ->whereIn('floor_id', $floorIds)
            ->distinct()
            ->pluck('floor_id');
        $this->assertGreaterThan(
            1,
            $distinctFloors->count(),
            sprintf(
                'Test premise no longer holds: NPC %d is no longer placed on multiple floors of %s\'s current mapping version (%d)',
                self::BRUTAL_NPC_ID,
                self::DUNGEON_KEY_MULTI_FLOOR,
                $mappingVersion->id,
            ),
        );

        // Pin the property that makes this test non-vacuous: a floor-unfiltered lookup (the
        // pre-#3935 disambiguation, minus the enemy_pack_id filter) must land on a *different*
        // floor than the obelisk icon's, otherwise this test would still pass even without the
        // floor anchor this PR adds.
        $firstMatchingEnemyRegardlessOfFloor = Enemy::where('npc_id', self::BRUTAL_NPC_ID)
            ->where('mapping_version_id', $mappingVersion->id)
            ->whereIn('floor_id', $floorIds)
            ->orderBy('id')
            ->firstOrFail();
        $this->assertNotSame(
            $obeliskMapIcon->floor_id,
            $firstMatchingEnemyRegardlessOfFloor->floor_id,
            'Test premise no longer holds: a floor-unfiltered enemy lookup now agrees with the '
            . 'obelisk icon\'s floor, so this test no longer exercises the floor-anchoring disambiguation.',
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

        // Assert - resolved on the obelisk icon's own floor, not an arbitrary one of the duplicates
        $this->assertCount(0, $result->getWarnings());
        $this->assertCount(1, $result->getMapIcons());
        $this->assertSame($obeliskMapIcon->floor_id, $result->getMapIcons()->first()['floor_id']);
    }

    /**
     * Guards #3915: when the NPC genuinely isn't seeded anywhere on the current mapping version
     * (real seeded case: Atal'Dazar), the lookup must not surface an uncaught
     * ModelNotFoundException - it should be recorded as a warning and that one skip omitted instead.
     */
    #[Test]
    public function parseRiftOffsets_givenRiftEnemyNotInCurrentMappingVersion_addsWarningInsteadOfThrowing(): void
    {
        // Arrange
        $dungeon        = Dungeon::where('key', self::DUNGEON_KEY_NO_ENEMY)->firstOrFail();
        $mappingVersion = $dungeon->getCurrentMappingVersion();

        // Pin the test's premise explicitly, so a future data/query change that makes this NPC
        // resolvable again fails loudly here with the reason, instead of as a confusing
        // "warnings count 0 != 1" a few lines down.
        $this->assertSame(
            0,
            Enemy::where('npc_id', self::BRUTAL_NPC_ID)
                ->where('mapping_version_id', $mappingVersion->id)
                ->count(),
            sprintf(
                'Test premise no longer holds: NPC %d now has an Enemy row on %s\'s current mapping version (%d)',
                self::BRUTAL_NPC_ID,
                self::DUNGEON_KEY_NO_ENEMY,
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
        $dungeon        = Dungeon::where('key', self::DUNGEON_KEY_UNPACKED_ONLY)->firstOrFail();
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
