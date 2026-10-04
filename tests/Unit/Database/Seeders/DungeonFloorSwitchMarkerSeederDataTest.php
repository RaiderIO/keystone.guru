<?php

namespace Tests\Unit\Database\Seeders;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('DatabaseSeeder')]
final class DungeonFloorSwitchMarkerSeederDataTest extends TestCase
{
    #[Test]
    #[DataProvider('continentProvider')]
    public function dungeonFloorSwitchMarkersJson_givenContinent_hidesExactlyOneMarkerOfEveryLinkedPairInFacade(string $dungeonFolder): void
    {
        // Arrange
        /** @var array<int, array{id: int, linked_dungeon_floor_switch_marker_id: int|null, hidden_in_facade: bool}> $markersById */
        $markersById = [];
        $filePaths   = glob(database_path(sprintf('seeders/dungeondata/%s/*/dungeon_floor_switch_markers.json', $dungeonFolder)));
        $this->assertNotEmpty($filePaths, sprintf('No dungeon floor switch marker files found for %s.', $dungeonFolder));

        foreach ($filePaths as $filePath) {
            $contents = file_get_contents($filePath);
            $this->assertIsString($contents, sprintf('Unable to read %s.', $filePath));

            foreach (json_decode($contents, true, flags: JSON_THROW_ON_ERROR) as $marker) {
                $markersById[$marker['id']] = $marker;
            }
        }

        // Act
        $pairCount                = 0;
        $pairsNotHidingExactlyOne = [];
        foreach ($markersById as $id => $marker) {
            $linkedId = $marker['linked_dungeon_floor_switch_marker_id'];
            if ($linkedId === null || $linkedId < $id) {
                continue;
            }

            $pairCount++;
            $hiddenCount = (int)$marker['hidden_in_facade'] + (int)($markersById[$linkedId]['hidden_in_facade'] ?? false);
            if ($hiddenCount !== 1) {
                $pairsNotHidingExactlyOne[] = sprintf('%d <-> %d hides %d', $id, $linkedId, $hiddenCount);
            }
        }

        // Assert
        $this->assertGreaterThan(0, $pairCount, sprintf('%s has no linked floor switch marker pairs.', $dungeonFolder));
        $this->assertSame(
            [],
            $pairsNotHidingExactlyOne,
            'Every zone-to-zone link must show exactly one marker on the continent facade.',
        );
    }

    /**
     * @return array<string, array{string}>
     */
    public static function continentProvider(): array
    {
        return [
            'Kalimdor'         => ['classic/kalimdor'],
            'Eastern Kingdoms' => ['classic/eastern_kingdoms'],
        ];
    }
}
