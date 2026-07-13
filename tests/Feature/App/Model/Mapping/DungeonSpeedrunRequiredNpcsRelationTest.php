<?php

namespace Tests\Feature\App\Model\Mapping;

use App\Models\Dungeon;
use App\Models\Floor\Floor;
use App\Models\Speedrun\DungeonSpeedrunRequiredNpc;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Mapping')]
final class DungeonSpeedrunRequiredNpcsRelationTest extends PublicTestCase
{
    #[Test]
    public function dungeonSpeedrunRequiredNpcs_givenAllDifficultiesOnFloor_returnsAllDifficulties(): void
    {
        // Arrange
        $floor       = $this->firstSeededFloor();
        $createdNpcs = $this->createRequiredNpcForEachDifficulty($floor->id);

        try {
            // Act
            $requiredNpcs = Floor::findOrFail($floor->id)->dungeonSpeedrunRequiredNpcs;

            // Assert
            $this->assertEqualsCanonicalizing(
                array_values(Dungeon::DIFFICULTY_ALL),
                $requiredNpcs->whereIn('id', $createdNpcs->pluck('id'))->pluck('difficulty')->all(),
            );
        } finally {
            DungeonSpeedrunRequiredNpc::whereIn('id', $createdNpcs->pluck('id'))->delete();
        }
    }

    #[Test]
    public function dungeonSpeedrunRequiredNpcs_givenAllDifficultiesOnFloor_returnsThemThroughDungeon(): void
    {
        // Arrange
        $floor       = $this->firstSeededFloor();
        $createdNpcs = $this->createRequiredNpcForEachDifficulty($floor->id);

        try {
            // Act
            $requiredNpcs = Dungeon::findOrFail($floor->dungeon_id)->dungeonSpeedrunRequiredNpcs;

            // Assert
            $this->assertEqualsCanonicalizing(
                array_values(Dungeon::DIFFICULTY_ALL),
                $requiredNpcs->whereIn('id', $createdNpcs->pluck('id'))->pluck('difficulty')->all(),
            );
        } finally {
            DungeonSpeedrunRequiredNpc::whereIn('id', $createdNpcs->pluck('id'))->delete();
        }
    }

    private function firstSeededFloor(): Floor
    {
        $floor = Floor::whereNotNull('dungeon_id')->first();

        if ($floor === null) {
            $this->fail('No seeded floor found for testing the speedrun required NPCs relation.');
        }

        return $floor;
    }

    /**
     * @return Collection<int, DungeonSpeedrunRequiredNpc>
     */
    private function createRequiredNpcForEachDifficulty(int $floorId): Collection
    {
        return collect(array_values(Dungeon::DIFFICULTY_ALL))->map(static fn(int $difficulty): DungeonSpeedrunRequiredNpc => DungeonSpeedrunRequiredNpc::create([
            'floor_id'   => $floorId,
            'difficulty' => $difficulty,
            'count'      => 1,
        ]));
    }
}
