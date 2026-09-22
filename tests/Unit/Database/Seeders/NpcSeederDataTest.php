<?php

namespace Tests\Unit\Database\Seeders;

use App\Models\Npc\NpcClassification;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('DatabaseSeeder')]
final class NpcSeederDataTest extends TestCase
{
    #[Test]
    public function npcsJson_givenEverySeededNpc_assignsEachNpcToAtLeastOneDungeon(): void
    {
        // Arrange - Npc::getGameVersionId() derives the game version from the NPC's dungeons, and has no
        // fallback for an NPC without any (#4601)
        $contents = file_get_contents(database_path('seeders/dungeondata/npcs.json'));
        $this->assertIsString($contents, 'Unable to read npcs.json.');

        /** @var array<int, array{id: int, npc_dungeons?: array<int, array<string, mixed>>}> $npcs */
        $npcs = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

        // Act
        $dungeonlessNpcIds = collect($npcs)
            ->filter(static fn(array $npc): bool => empty($npc['npc_dungeons']))
            ->pluck('id')
            ->values()
            ->all();

        // Assert
        $this->assertNotEmpty($npcs, 'npcs.json should hold seeded NPCs.');
        $this->assertSame([], $dungeonlessNpcIds, 'These NPCs in npcs.json have no npc_dungeons row.');
    }

    #[Test]
    public function npcsJson_givenEveryBossAndRareNpc_marksEachNpcDangerous(): void
    {
        // Arrange
        $contents = file_get_contents(database_path('seeders/dungeondata/npcs.json'));
        $this->assertIsString($contents, 'Unable to read npcs.json.');

        /** @var array<int, array{id: int, classification_id: int, dangerous: int}> $npcs */
        $npcs = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

        $alwaysDangerousClassificationIds = [
            NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_BOSS],
            NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_FINAL_BOSS],
            NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_RARE],
        ];

        // Act
        $alwaysDangerousNpcs = collect($npcs)
            ->filter(static fn(array $npc): bool => in_array($npc['classification_id'], $alwaysDangerousClassificationIds, true));
        $notDangerousNpcIds = $alwaysDangerousNpcs
            ->filter(static fn(array $npc): bool => !$npc['dangerous'])
            ->pluck('id')
            ->values()
            ->all();

        // Assert
        $this->assertNotEmpty($alwaysDangerousNpcs, 'npcs.json should hold boss and rare NPCs.');
        $this->assertSame([], $notDangerousNpcIds, 'These boss or rare NPCs in npcs.json are not marked dangerous.');
    }
}
