<?php

namespace Tests\Feature\App\Logic\MapContext;

use App\Models\Dungeon;
use App\Models\Faction;
use App\Models\Mapping\MappingVersion;
use App\Service\MapContext\MapContextServiceInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('MapContext')]
final class MapContextMappingVersionEditTest extends PublicTestCase
{
    #[Test]
    public function toArray_givenMappingVersionEditContext_returnsFactionKeyInsteadOfTranslatedName(): void
    {
        // Arrange
        $mappingVersion = MappingVersion::query()->firstOrFail();
        $dungeon        = Dungeon::query()->findOrFail($mappingVersion->dungeon_id);

        // Act
        $context = app(MapContextServiceInterface::class)
            ->createMapContextMappingVersionEdit($dungeon, $mappingVersion)
            ->toArray();

        // Assert
        $this->assertSame(Faction::FACTION_ANY, $context['faction']);
    }

    #[Test]
    public function toArray_givenMappingVersionEditContext_listsEveryOtherDungeonForTheTargetDungeonSelect(): void
    {
        // Arrange
        $mappingVersion = MappingVersion::query()->firstOrFail();
        $dungeon        = Dungeon::query()->findOrFail($mappingVersion->dungeon_id);
        /** @var Dungeon $otherDungeon */
        $otherDungeon = Dungeon::query()->whereKeyNot($dungeon->id)->firstOrFail();

        // Act
        /** @var array<int, array{id: int, name: string}> $dungeonSelectValues */
        $dungeonSelectValues = app(MapContextServiceInterface::class)
            ->createMapContextMappingVersionEdit($dungeon, $mappingVersion)
            ->toArray()['dungeonSelectValues'];
        $dungeonSelectValues = collect($dungeonSelectValues);

        // Assert
        $this->assertSame(Dungeon::query()->count() - 1, $dungeonSelectValues->count());
        $this->assertContains(['id' => $otherDungeon->id, 'name' => $otherDungeon->name], $dungeonSelectValues->all());
        $this->assertNull($dungeonSelectValues->firstWhere('id', $dungeon->id));
    }
}
