<?php

namespace Tests\Feature\App\Service\Dungeon\DungeonService;

use App\Models\Dungeon;
use App\Models\DungeonKey;
use App\Service\Dungeon\DungeonServiceInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('DungeonService')]
#[Group('ImportInstanceIdsFromCsv')]
final class ImportInstanceIdsFromCsvTest extends PublicTestCase
{
    private const int INSTANCE_ID_FOR_MAP_ID_ZERO  = 999_801;
    private const int INSTANCE_ID_FOR_EMPTY_MAP_ID = 999_802;

    #[Test]
    public function importInstanceIdsFromCsv_givenMapIdZeroAndAnEmptyMapId_onlyUpdatesEasternKingdoms(): void
    {
        // Arrange
        $easternKingdoms = Dungeon::query()->where('key', DungeonKey::EASTERN_KINGDOMS->value)->firstOrFail();
        $this->assertNull($easternKingdoms->instance_id);
        $this->assertTrue(Dungeon::query()->whereNull('map_id')->whereNull('instance_id')->exists());

        $filePath = tempnam(sys_get_temp_dir(), 'instance_ids');
        file_put_contents($filePath, implode("\n", [
            'ID,MapID',
            sprintf('%d,0', self::INSTANCE_ID_FOR_MAP_ID_ZERO),
            sprintf('%d,', self::INSTANCE_ID_FOR_EMPTY_MAP_ID),
        ]));

        try {
            // Act
            $result = app(DungeonServiceInterface::class)->importInstanceIdsFromCsv($filePath);

            // Assert
            $this->assertTrue($result);
            $this->assertSame(self::INSTANCE_ID_FOR_MAP_ID_ZERO, $easternKingdoms->fresh()->instance_id);
            $this->assertFalse(Dungeon::query()->where('instance_id', self::INSTANCE_ID_FOR_EMPTY_MAP_ID)->exists());
        } finally {
            Dungeon::query()
                ->whereIn('instance_id', [self::INSTANCE_ID_FOR_MAP_ID_ZERO, self::INSTANCE_ID_FOR_EMPTY_MAP_ID])
                ->update(['instance_id' => null]);
            unlink($filePath);
        }
    }
}
