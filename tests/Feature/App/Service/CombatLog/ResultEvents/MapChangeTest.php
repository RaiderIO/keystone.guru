<?php

namespace Tests\Feature\App\Service\CombatLog\ResultEvents;

use App\Logic\CombatLog\CombatLogEntry;
use App\Logic\CombatLog\CombatLogVersion;
use App\Logic\CombatLog\SpecialEvents\MapChange as MapChangeEvent;
use App\Models\Dungeon;
use App\Models\DungeonKey;
use App\Service\CombatLog\Exceptions\FloorNotSupportedException;
use App\Service\CombatLog\ResultEvents\MapChange;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * Naxxramas and its classic version list the same instance map, so their floors share ui_map_ids 162-167.
 */
#[Group('CombatLog')]
#[Group('MapChange')]
final class MapChangeTest extends PublicTestCase
{
    private const int UI_MAP_ID_NAXXRAMAS = 162;

    private const int UI_MAP_ID_UNKNOWN = 999999;

    private const string DUNGEON_KEY_NAXXRAMAS = 'naxxramas';

    private const string DUNGEON_KEY_NAXXRAMAS_CLASSIC = 'naxxramas_classic';

    #[Test]
    #[DataProvider('naxxramasDungeonKeyProvider')]
    public function getFloorInDungeon_givenUiMapIdSharedWithAnotherDungeon_returnsTheFloorOfTheGivenDungeon(string $dungeonKey): void
    {
        // Arrange
        $dungeon   = Dungeon::query()->where('key', $dungeonKey)->firstOrFail();
        $mapChange = new MapChange($this->createMapChangeEvent(self::UI_MAP_ID_NAXXRAMAS));

        // Act
        $result = $mapChange->getFloorInDungeon($dungeon->id);

        // Assert
        $this->assertSame($dungeon->id, $result?->dungeon_id);
        $this->assertSame(self::UI_MAP_ID_NAXXRAMAS, $result?->ui_map_id);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function naxxramasDungeonKeyProvider(): array
    {
        return [
            'Naxxramas'           => [self::DUNGEON_KEY_NAXXRAMAS],
            'Naxxramas (classic)' => [self::DUNGEON_KEY_NAXXRAMAS_CLASSIC],
        ];
    }

    #[Test]
    public function getFloorInDungeon_givenDungeonWithoutTheUiMapId_returnsNull(): void
    {
        // Arrange
        $dungeon   = Dungeon::query()->where('key', DungeonKey::MAGISTERS_TERRACE_MIDNIGHT->value)->firstOrFail();
        $mapChange = new MapChange($this->createMapChangeEvent(self::UI_MAP_ID_NAXXRAMAS));

        // Act
        $result = $mapChange->getFloorInDungeon($dungeon->id);

        // Assert
        $this->assertNull($result);
    }

    #[Test]
    #[DataProvider('unsupportedUiMapIdProvider')]
    public function construct_givenUiMapIdNoFloorCarries_throwsFloorNotSupportedException(int $uiMapId): void
    {
        // Arrange
        $mapChangeEvent = $this->createMapChangeEvent($uiMapId);

        // Assert
        $this->expectException(FloorNotSupportedException::class);

        // Act
        new MapChange($mapChangeEvent);
    }

    /**
     * Floors without a ui_map_id store 0, so 0 must not match them.
     *
     * @return array<string, array{0: int}>
     */
    public static function unsupportedUiMapIdProvider(): array
    {
        return [
            'unknown ui_map_id' => [self::UI_MAP_ID_UNKNOWN],
            'ui_map_id zero'    => [0],
        ];
    }

    private function createMapChangeEvent(int $uiMapId): MapChangeEvent
    {
        $combatLogEntry = new CombatLogEntry(
            sprintf('3/25/2026 10:36:28.9051  MAP_CHANGE,%d,"Naxxramas",3615.830078,2886.610107,-2640.270020,-3734.100098', $uiMapId),
        );

        /** @var MapChangeEvent $mapChangeEvent */
        $mapChangeEvent = $combatLogEntry->parseEvent([], CombatLogVersion::RETAIL_12_0_1);

        return $mapChangeEvent;
    }
}
