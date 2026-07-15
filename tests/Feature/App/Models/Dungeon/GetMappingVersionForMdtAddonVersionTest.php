<?php

namespace Tests\Feature\App\Models\Dungeon;

use App\Models\Dungeon;
use App\Models\GameVersion\GameVersion;
use App\Models\Mapping\MappingVersion;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * Verifies that an imported MDT string is attached to the mapping version matching the MDT version the
 * string was built with (#3380). Uses the real, committed addonVersion => release-date map, so the
 * ordering traps in MDT's version scheme (e.g. 40120 predates 5014 despite being numerically larger)
 * are exercised end to end.
 *
 * Real map anchors used below:
 *   40120 => 2022-11-28   4351 => 2024-03-21   5014 => 2024-09-28   6114 => 2026-06-04   6115 => 2026-06-08
 */
#[Group('MDT')]
#[Group('MDTAddonVersion')]
final class GetMappingVersionForMdtAddonVersionTest extends PublicTestCase
{
    private Dungeon $dungeon;

    private GameVersion $gameVersion;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gameVersion = GameVersion::getDefaultGameVersion();

        // Replicate a seeded dungeon so we get a fresh id with zero existing mapping versions - this
        // isolates the candidate set to only the mapping versions this test creates.
        $this->dungeon      = Dungeon::query()->firstOrFail()->replicate();
        $this->dungeon->key = 'test-3380-' . uniqid();
        $this->dungeon->save();
    }

    protected function tearDown(): void
    {
        MappingVersion::query()->where('dungeon_id', $this->dungeon->id)->delete();
        Dungeon::query()->where('id', $this->dungeon->id)->delete();

        parent::tearDown();
    }

    #[Test]
    public function getMappingVersionForMdtAddonVersion_givenExactMatch_returnsThatMappingVersion(): void
    {
        // Arrange
        $this->createMappingVersion(1, 40120, '2022-11-28 00:00:00');
        $expected = $this->createMappingVersion(2, 5014, '2024-09-28 00:00:00');
        $this->createMappingVersion(3, 6115, '2026-06-08 00:00:00');

        // Act
        $result = $this->reloadDungeon()->getMappingVersionForMdtAddonVersion(5014, $this->gameVersion);

        // Assert
        $this->assertNotNull($result);
        $this->assertSame($expected->id, $result->id);
    }

    #[Test]
    public function getMappingVersionForMdtAddonVersion_givenVersionBetweenTwoImports_returnsTheLaterMappingVersion(): void
    {
        // Arrange - MDT v6.1.14 (6114, 2026-06-04) sits between an import of v5.0.14 and a later import of v6.1.15
        $this->createMappingVersion(1, 5014, '2024-09-28 00:00:00');
        $expected = $this->createMappingVersion(2, 6115, '2026-06-08 00:00:00');

        // Act
        $result = $this->reloadDungeon()->getMappingVersionForMdtAddonVersion(6114, $this->gameVersion);

        // Assert
        $this->assertNotNull($result);
        $this->assertSame($expected->id, $result->id, 'A version released between two imports must attach to the later mapping version.');
    }

    #[Test]
    public function getMappingVersionForMdtAddonVersion_givenNonOrderableInts_selectsByDateNotInteger(): void
    {
        // Arrange - 40120 (2022) is numerically larger than 4351 (2024) yet older; an int comparison would break.
        $this->createMappingVersion(1, 40120, '2022-11-28 00:00:00');
        $expected = $this->createMappingVersion(2, 5014, '2024-09-28 00:00:00');

        // Act - string built with MDT v4.3.5.1 (4351, 2024-03-21)
        $result = $this->reloadDungeon()->getMappingVersionForMdtAddonVersion(4351, $this->gameVersion);

        // Assert - resolved by date, so it lands on the 2024-09 import, not the numerically-closest 40120
        $this->assertNotNull($result);
        $this->assertSame($expected->id, $result->id);
    }

    #[Test]
    public function getMappingVersionForMdtAddonVersion_givenVersionNewerThanEveryImport_returnsCurrent(): void
    {
        // Arrange
        $this->createMappingVersion(1, 40120, '2022-11-28 00:00:00');
        $current = $this->createMappingVersion(2, 5014, '2024-09-28 00:00:00');

        // Act - string newer than anything imported (user is ahead of the server)
        $result = $this->reloadDungeon()->getMappingVersionForMdtAddonVersion(6115, $this->gameVersion);

        // Assert - falls back to the current (highest version) mapping version
        $this->assertNotNull($result);
        $this->assertSame($current->id, $result->id);
    }

    #[Test]
    public function getMappingVersionForMdtAddonVersion_givenVersionOlderThanEveryImport_returnsOldestMappingVersion(): void
    {
        // Arrange
        $oldest = $this->createMappingVersion(1, 5014, '2024-09-28 00:00:00');
        $this->createMappingVersion(2, 6115, '2026-06-08 00:00:00');

        // Act - string predates every mapping version we have
        $result = $this->reloadDungeon()->getMappingVersionForMdtAddonVersion(40120, $this->gameVersion);

        // Assert
        $this->assertNotNull($result);
        $this->assertSame($oldest->id, $result->id);
    }

    #[Test]
    public function getMappingVersionForMdtAddonVersion_givenNoAddonVersion_returnsCurrent(): void
    {
        // Arrange
        $this->createMappingVersion(1, 40120, '2022-11-28 00:00:00');
        $current = $this->createMappingVersion(2, 5014, '2024-09-28 00:00:00');

        // Act - Keystone's own exports and very old strings carry no addonVersion
        $result = $this->reloadDungeon()->getMappingVersionForMdtAddonVersion(null, $this->gameVersion);

        // Assert
        $this->assertNotNull($result);
        $this->assertSame($current->id, $result->id);
    }

    #[Test]
    public function getMappingVersionForMdtAddonVersion_givenUnknownAddonVersion_returnsCurrent(): void
    {
        // Arrange
        $this->createMappingVersion(1, 40120, '2022-11-28 00:00:00');
        $current = $this->createMappingVersion(2, 5014, '2024-09-28 00:00:00');

        // Act - an addonVersion not present in the map (e.g. newer than what has been synced)
        $result = $this->reloadDungeon()->getMappingVersionForMdtAddonVersion(999999, $this->gameVersion);

        // Assert
        $this->assertNotNull($result);
        $this->assertSame($current->id, $result->id);
    }

    #[Test]
    public function getMappingVersionForMdtAddonVersion_givenTiedImportedFromDate_returnsHighestVersion(): void
    {
        // Arrange - two mapping versions imported from the same MDT version (e.g. a manual/facade clone)
        $this->createMappingVersion(1, 5014, '2024-09-28 00:00:00');
        $expected = $this->createMappingVersion(2, 5014, '2024-10-05 00:00:00');
        $this->createMappingVersion(3, 6115, '2026-06-08 00:00:00');

        // Act
        $result = $this->reloadDungeon()->getMappingVersionForMdtAddonVersion(5014, $this->gameVersion);

        // Assert - the higher version among the tied candidates wins
        $this->assertNotNull($result);
        $this->assertSame($expected->id, $result->id);
    }

    #[Test]
    public function getMappingVersionForMdtAddonVersion_givenNullColumnFallsBackToCreatedAt(): void
    {
        // Arrange - a legacy mapping version with no mdt_addon_version; selection must use its created_at
        $expected = $this->createMappingVersion(1, null, '2023-01-01 00:00:00');

        // Act - string built with MDT v4.0.1.20 (40120, 2022-11-28), before the mapping version's created_at
        $result = $this->reloadDungeon()->getMappingVersionForMdtAddonVersion(40120, $this->gameVersion);

        // Assert
        $this->assertNotNull($result);
        $this->assertSame($expected->id, $result->id);
    }

    private function createMappingVersion(int $version, ?int $addonVersion, string $createdAt): MappingVersion
    {
        // insertGetId bypasses the clone-on-create boot, giving us exactly the candidate rows we define.
        $id = MappingVersion::query()->insertGetId([
            'dungeon_id'            => $this->dungeon->id,
            'game_version_id'       => $this->gameVersion->id,
            'version'               => $version,
            'enemy_forces_required' => 0,
            'timer_max_seconds'     => 0,
            'facade_enabled'        => false,
            'mdt_addon_version'     => $addonVersion,
            'created_at'            => $createdAt,
            'updated_at'            => $createdAt,
        ]);

        return MappingVersion::query()->findOrFail($id);
    }

    private function reloadDungeon(): Dungeon
    {
        // Fresh instance so the per-request current-mapping-version cache does not carry over.
        return Dungeon::query()->findOrFail($this->dungeon->id);
    }
}
