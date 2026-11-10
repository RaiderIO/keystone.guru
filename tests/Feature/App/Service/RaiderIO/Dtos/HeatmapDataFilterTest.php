<?php

namespace Tests\Feature\App\Service\RaiderIO\Dtos;

use App\Models\CombatLog\CombatLogEventDataType;
use App\Models\CombatLog\CombatLogEventEventType;
use App\Models\Dungeon;
use App\Models\DungeonKey;
use App\Service\RaiderIO\Dtos\HeatmapDataFilter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('RaiderIO')]
#[Group('HeatmapDataFilter')]
final class HeatmapDataFilterTest extends PublicTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'keystoneguru.heatmap.api.min_required_sample_factor_default' => 0.0005,
            'keystoneguru.heatmap.api.floors_as_array'                    => true,
        ]);
    }

    #[Test]
    public function toArray_givenDungeon_returnsTheMythicPlusParametersInTheirOriginalOrder(): void
    {
        // Arrange
        $dungeon = Dungeon::where('key', DungeonKey::THE_STONEVAULT->value)->firstOrFail();
        $filter  = HeatmapDataFilter::fromArray($this->getRequestArray($dungeon));

        // Act
        $result = $filter->toArray();

        // Assert
        $this->assertSame([
            'challengeModeId'          => $dungeon->challenge_mode_id,
            'type'                     => CombatLogEventEventType::PlayerDeath->value,
            'dataType'                 => CombatLogEventDataType::PlayerPosition->value,
            'minMythicLevel'           => 10,
            'maxMythicLevel'           => 12,
            'minItemLevel'             => 600,
            'maxItemLevel'             => 700,
            'minPlayerDeaths'          => 0,
            'maxPlayerDeaths'          => 3,
            'minTimerFraction'         => 0.5,
            'maxTimerFraction'         => 1.0,
            'excludeAffixIds'          => '9',
            'excludeClassIds'          => '1',
            'season'                   => 'season-mn-1',
            'region'                   => 'eu',
            'minRequiredSamplesFactor' => 0.0005,
            'floorsAsArray'            => 'true',
        ], $result);
    }

    #[Test]
    #[DataProvider('continentProvider')]
    public function toArray_givenContinent_identifiesItByMapIdWithoutMythicPlusParameters(DungeonKey $dungeonKey, int $expectedMapId): void
    {
        // Arrange
        $dungeon = Dungeon::where('key', $dungeonKey->value)->firstOrFail();
        $filter  = HeatmapDataFilter::fromArray($this->getRequestArray($dungeon));

        // Act
        $result = $filter->toArray();

        // Assert
        $this->assertSame([
            HeatmapDataFilter::CONTINENT_IDENTIFIER_PARAMETER => $expectedMapId,
            'type'                                            => CombatLogEventEventType::PlayerDeath->value,
            'dataType'                                        => CombatLogEventDataType::PlayerPosition->value,
            'minItemLevel'                                    => 600,
            'maxItemLevel'                                    => 700,
            'excludeClassIds'                                 => '1',
            'region'                                          => 'eu',
            'minRequiredSamplesFactor'                        => 0.0005,
            'floorsAsArray'                                   => 'true',
        ], $result);
    }

    /**
     * @return array<string, array{0: DungeonKey, 1: int}>
     */
    public static function continentProvider(): array
    {
        return [
            'kalimdor' => [DungeonKey::KALIMDOR, 1],
            // map_id 0 must still be sent - a 0 is a valid value, not an empty one
            'eastern kingdoms' => [DungeonKey::EASTERN_KINGDOMS, 0],
        ];
    }

    #[Test]
    public function toArray_givenContinentFilterBuiltWithMythicPlusSetters_stillOmitsThem(): void
    {
        // Arrange
        $dungeon = Dungeon::where('key', DungeonKey::KALIMDOR->value)->firstOrFail();
        $filter  = new HeatmapDataFilter($dungeon, CombatLogEventEventType::PlayerDeath, CombatLogEventDataType::PlayerPosition);
        $filter->setIncludePlayerSpellIds(collect())
            ->setKeyLevelMin(10)
            ->setKeyLevelMax(12)
            ->setTimerFractionMin(0.5)
            ->setTimerFractionMax(1.0)
            ->setPlayerDeathsMin(0)
            ->setPlayerDeathsMax(3)
            ->setExcludeAffixIds('9')
            ->setSeason('season-mn-1');

        // Act
        $result = $filter->toArray();

        // Assert
        $this->assertSame(1, $result[HeatmapDataFilter::CONTINENT_IDENTIFIER_PARAMETER]);
        $this->assertSame([], array_intersect_key($result, array_flip(HeatmapDataFilter::MYTHIC_PLUS_PARAMETERS)));
    }

    #[Test]
    public function fromArray_givenContinentWithMythicPlusFilters_leavesThemUnset(): void
    {
        // Arrange
        $dungeon = Dungeon::where('key', DungeonKey::KALIMDOR->value)->firstOrFail();

        // Act
        $filter = HeatmapDataFilter::fromArray($this->getRequestArray($dungeon));

        // Assert
        $this->assertNull($filter->getKeyLevelMin());
        $this->assertNull($filter->getKeyLevelMax());
        $this->assertNull($filter->getTimerFractionMin());
        $this->assertNull($filter->getTimerFractionMax());
        $this->assertNull($filter->getPlayerDeathsMin());
        $this->assertNull($filter->getPlayerDeathsMax());
        $this->assertNull($filter->getExcludeAffixIds());
        $this->assertNull($filter->getSeason());
        // Positive control: a filter that does mean something in the open world is kept
        $this->assertSame(600, $filter->getItemLevelMin());
    }

    /**
     * @return array<string, mixed>
     */
    private function getRequestArray(Dungeon $dungeon): array
    {
        return [
            'dungeonId'        => $dungeon->id,
            'type'             => CombatLogEventEventType::PlayerDeath->value,
            'dataType'         => CombatLogEventDataType::PlayerPosition->value,
            'region'           => 'eu',
            'minMythicLevel'   => 10,
            'maxMythicLevel'   => 12,
            'minItemLevel'     => 600,
            'maxItemLevel'     => 700,
            'minPlayerDeaths'  => 0,
            'maxPlayerDeaths'  => 3,
            'minTimerFraction' => 0.5,
            'maxTimerFraction' => 1.0,
            'excludeAffixIds'  => '9',
            'excludeClassIds'  => '1',
            'season'           => 'season-mn-1',
        ];
    }
}
