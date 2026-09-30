<?php

namespace Tests\Feature\App\Model\Dungeon;

use App\Models\Dungeon;
use App\Models\DungeonStart;
use App\Models\Floor\Floor;
use App\Models\Mapping\MappingVersion;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Dungeon')]
#[Group('DungeonStart')]
final class DungeonDungeonStartsTest extends PublicTestCase
{
    #[Test]
    public function dungeonStarts_givenStartsOnOwnAndOtherDungeonFloors_returnsOnlyOwnStarts(): void
    {
        // Arrange
        [$ownFloor, $otherFloor] = $this->floorsOfTwoDungeons();

        $ownMappingVersionId   = MappingVersion::query()->where('dungeon_id', $ownFloor->dungeon_id)->value('id');
        $otherMappingVersionId = MappingVersion::query()->where('dungeon_id', $otherFloor->dungeon_id)->value('id');

        $ownStart = DungeonStart::factory()->create([
            'mapping_version_id' => $ownMappingVersionId,
            'floor_id'           => $ownFloor->id,
        ]);
        $otherStart = DungeonStart::factory()->create([
            'mapping_version_id' => $otherMappingVersionId,
            'floor_id'           => $otherFloor->id,
        ]);

        try {
            // Act
            $dungeonStartIds = Dungeon::findOrFail($ownFloor->dungeon_id)->dungeonStarts->pluck('id');

            // Assert
            $this->assertContains($ownStart->id, $dungeonStartIds);
            $this->assertNotContains($otherStart->id, $dungeonStartIds);
        } finally {
            $ownStart->delete();
            $otherStart->delete();
        }
    }

    #[Test]
    public function dungeonStarts_givenStartsInTwoMappingVersionsOfTheDungeon_returnsBoth(): void
    {
        // Arrange
        [$floor] = $this->floorsOfTwoDungeons();

        $mappingVersionIds = MappingVersion::query()
            ->where('dungeon_id', $floor->dungeon_id)
            ->orderBy('id')
            ->limit(2)
            ->pluck('id');
        $olderMappingVersionId = $mappingVersionIds->first();
        $newerMappingVersionId = $mappingVersionIds->last();

        $olderStart = DungeonStart::factory()->create([
            'mapping_version_id' => $olderMappingVersionId,
            'floor_id'           => $floor->id,
        ]);
        $newerStart = DungeonStart::factory()->create([
            'mapping_version_id' => $newerMappingVersionId,
            'floor_id'           => $floor->id,
        ]);

        try {
            // Act
            $dungeonStartIds = Dungeon::findOrFail($floor->dungeon_id)->dungeonStarts->pluck('id');

            // Assert
            $this->assertContains($olderStart->id, $dungeonStartIds);
            $this->assertContains($newerStart->id, $dungeonStartIds);
        } finally {
            $olderStart->delete();
            $newerStart->delete();
        }
    }

    /**
     * @return array{Floor, Floor}
     */
    private function floorsOfTwoDungeons(): array
    {
        $ownFloor   = Floor::query()->whereNotNull('dungeon_id')->whereHas('dungeon.mappingVersions')->firstOrFail();
        $otherFloor = Floor::query()
            ->whereNotNull('dungeon_id')
            ->where('dungeon_id', '!=', $ownFloor->dungeon_id)
            ->whereHas('dungeon.mappingVersions')
            ->firstOrFail();

        return [$ownFloor, $otherFloor];
    }
}
