<?php

namespace Tests\Unit\Database\Seeders;

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
}
