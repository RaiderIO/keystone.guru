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
}
