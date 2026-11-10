<?php

namespace Tests\Feature\App\Service\RaiderIO\Dtos;

use App\Models\CharacterClass;
use App\Models\CharacterClassSpecialization;
use App\Models\CombatLog\CombatLogEventDataType;
use App\Models\CombatLog\CombatLogEventEventType;
use App\Models\Dungeon;
use App\Models\DungeonKey;
use App\Models\Spell\Spell;
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
    public function toArray_givenContinent_identifiesItByMapIdWithoutUnsupportedParameters(DungeonKey $dungeonKey, int $expectedMapId): void
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
    public function toArray_givenContinentFilterBuiltWithUnsupportedSetters_stillOmitsThem(): void
    {
        // Arrange
        $dungeon = Dungeon::where('key', DungeonKey::KALIMDOR->value)->firstOrFail();
        $filter  = new HeatmapDataFilter($dungeon, CombatLogEventEventType::PlayerDeath, CombatLogEventDataType::PlayerPosition);
        $filter->setKeyLevelMin(10)
            ->setKeyLevelMax(12)
            ->setTimerFractionMin(0.5)
            ->setTimerFractionMax(1.0)
            ->setPlayerDeathsMin(0)
            ->setPlayerDeathsMax(3)
            ->setExcludeAffixIds('9')
            ->setExcludeClassIds('1')
            ->setSeason('season-mn-1');

        // Act
        $result = $filter->toArray();

        // Assert
        $this->assertSame(1, $result[HeatmapDataFilter::CONTINENT_IDENTIFIER_PARAMETER] ?? null);
        $this->assertSame([], array_intersect_key($result, array_flip(HeatmapDataFilter::CONTINENT_UNSUPPORTED_PARAMETERS)));
    }

    #[Test]
    public function fromArray_givenContinentWithUnsupportedFilters_leavesThemUnset(): void
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
        $this->assertNull($filter->getExcludeClassIds());
        // Positive control: a filter that does mean something in the open world is kept
        $this->assertSame(600, $filter->getItemLevelMin());
        $this->assertSame(700, $filter->getItemLevelMax());
    }

    #[Test]
    public function toArray_givenContinentWithClassSpecSpellAndWeekFilters_keepsOnlyTheWeek(): void
    {
        // Arrange
        $dungeon = Dungeon::where('key', DungeonKey::KALIMDOR->value)->firstOrFail();
        $filter  = HeatmapDataFilter::fromArray($this->getClassSpecSpellAndWeekRequestArray($dungeon));

        // Act
        $result = $filter->toArray();

        // Assert
        $this->assertSame([], array_intersect_key($result, array_flip(HeatmapDataFilter::CONTINENT_UNSUPPORTED_PARAMETERS)));
        $this->assertSame(1000, $result['minPeriod'] ?? null);
        $this->assertSame(1003, $result['maxPeriod'] ?? null);
    }

    #[Test]
    public function toArray_givenDungeonWithClassSpecSpellAndWeekFilters_keepsThemAll(): void
    {
        // Arrange
        $dungeon = Dungeon::where('key', DungeonKey::THE_STONEVAULT->value)->firstOrFail();
        $filter  = HeatmapDataFilter::fromArray($this->getClassSpecSpellAndWeekRequestArray($dungeon));

        // Act
        $result = $filter->toArray();

        // Assert - every parameter but the dungeon id, which toArray() sends as the challengeModeId
        $requestArray = $this->getClassSpecSpellAndWeekRequestArray($dungeon);
        unset($requestArray['dungeonId']);
        $this->assertEqualsCanonicalizing(array_keys($requestArray), array_keys(array_intersect_key($result, $requestArray)));
    }

    /**
     * @return array<string, mixed>
     */
    private function getClassSpecSpellAndWeekRequestArray(Dungeon $dungeon): array
    {
        return [
            'dungeonId'                  => $dungeon->id,
            'type'                       => CombatLogEventEventType::PlayerDeath->value,
            'dataType'                   => CombatLogEventDataType::PlayerPosition->value,
            'includeClassIds'            => [CharacterClass::query()->firstOrFail()->class_id],
            'excludeClassIds'            => '2',
            'includeSpecIds'             => [CharacterClassSpecialization::query()->firstOrFail()->specialization_id],
            'excludeSpecIds'             => '3',
            'includePlayerDeathClassIds' => [CharacterClass::query()->firstOrFail()->class_id],
            'excludePlayerDeathClassIds' => '4',
            'includePlayerDeathSpecIds'  => [CharacterClassSpecialization::query()->firstOrFail()->specialization_id],
            'excludePlayerDeathSpecIds'  => '5',
            'includePlayerSpellIds'      => [Spell::query()->firstOrFail()->id],
            'minPeriod'                  => 1000,
            'maxPeriod'                  => 1003,
        ];
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
