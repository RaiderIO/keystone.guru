<?php

namespace Tests\Feature\Mapping;

use App\Models\Dungeon;
use App\Models\DungeonKey;
use App\Models\Enemy;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('Mapping')]
final class EnemyMdtIdTest extends TestCase
{
    #[Test]
    #[DataProvider('mdtId_givenCurrentMappingVersion_isUniquePerMdtNpcAndSeasonalIndex_Provider')]
    public function mdtId_givenCurrentMappingVersion_isUniquePerMdtNpcAndSeasonalIndex(DungeonKey $dungeonKey): void
    {
        // Arrange
        /** @var Dungeon $dungeon */
        $dungeon        = Dungeon::query()->where('key', $dungeonKey->value)->firstOrFail();
        $mappingVersion = $dungeon->getCurrentMappingVersion();

        // Act
        $duplicates = $mappingVersion->enemies()
            ->whereNotNull('mdt_id')
            ->get()
            ->groupBy(static fn(Enemy $enemy): string => sprintf(
                'npc %d, mdt_id %d, seasonal index %s',
                $enemy->getMdtNpcId(),
                $enemy->mdt_id,
                $enemy->seasonal_index ?? 'none',
            ))
            ->filter(static fn($enemies): bool => $enemies->count() > 1)
            ->map(static fn($enemies): array => $enemies->pluck('id')->all());

        // Assert
        $this->assertEmpty($duplicates, sprintf('Enemies share an mdt_id: %s', json_encode($duplicates)));
    }

    /**
     * @return array<string, array{DungeonKey}>
     */
    public static function mdtId_givenCurrentMappingVersion_isUniquePerMdtNpcAndSeasonalIndex_Provider(): array
    {
        return [
            'Plaguefall' => [DungeonKey::PLAGUEFALL],
            'Tol Dagor'  => [DungeonKey::TOL_DAGOR],
        ];
    }
}
