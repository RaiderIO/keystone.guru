<?php

namespace Tests\Unit\App\Models;

use App\Models\Dungeon;
use App\Models\DungeonKey;
use App\Models\DungeonSelectorGroup;
use App\Models\RaidKey;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Models')]
final class DungeonSelectorGroupTest extends PublicTestCase
{
    #[Test]
    #[DataProvider('selectorGroupProvider')]
    public function getSelectorGroup_givenAKey_returnsItsGroup(string $key, bool $raid, DungeonSelectorGroup $expected): void
    {
        // Arrange
        $dungeon = new Dungeon(['key' => $key, 'raid' => $raid]);

        // Act
        $selectorGroup = $dungeon->getSelectorGroup();

        // Assert
        $this->assertSame($expected, $selectorGroup);
    }

    /**
     * @return array<string, array{0: string, 1: bool, 2: DungeonSelectorGroup}>
     */
    public static function selectorGroupProvider(): array
    {
        return [
            'kalimdor is a continent'         => [DungeonKey::KALIMDOR->value, false, DungeonSelectorGroup::WORLD],
            'eastern kingdoms is a continent' => [DungeonKey::EASTERN_KINGDOMS->value, false, DungeonSelectorGroup::WORLD],
            'deadmines is a dungeon'          => [DungeonKey::DEADMINES->value, false, DungeonSelectorGroup::DUNGEON],
            'molten core is a raid'           => [RaidKey::MOLTEN_CORE->value, true, DungeonSelectorGroup::RAID],
            'an unknown key falls back'       => ['not_a_known_key', false, DungeonSelectorGroup::DUNGEON],
        ];
    }

    #[Test]
    public function sortOrder_givenEveryCase_followsDeclarationOrder(): void
    {
        // Arrange
        $cases = DungeonSelectorGroup::cases();

        // Act
        $sortOrders = array_map(static fn(DungeonSelectorGroup $group) => $group->sortOrder(), $cases);

        // Assert
        $this->assertSame(array_keys($cases), $sortOrders);
    }
}
