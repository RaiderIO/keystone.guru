<?php

namespace Tests\Feature\Controller\Dungeon;

use App\Models\CombatLog\CombatLogEventEventType;
use App\Models\Dungeon;
use App\Models\DungeonKey;
use App\Models\Floor\Floor;
use App\Models\GameVersion\GameVersion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\ProvidesDungeon;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('DungeonHeatmap')]
final class DungeonHeatmapControllerFilterSetTest extends PublicTestCase
{
    use ProvidesDungeon;

    /** The element ids of the open-world filter set: death type, region, item level and week */
    private const array CONTINENT_FILTER_IDS = [
        'filter_event_type_container',
        'filter_region_container',
        'filter_item_level',
        'filter_weeks',
    ];

    /** The element ids of the Mythic+ filters a continent does not offer */
    private const array MYTHIC_PLUS_FILTER_IDS = [
        'filter_data_type_container',
        'filter_player_spells',
        'filter_key_level',
        'filter_player_deaths',
        'filter_duration',
        'filter_weekly_affix_groups',
        'filter_classes',
        'filter_specializations',
        'filter_classes_player_deaths',
        'filter_specializations_player_deaths',
    ];

    /** The query parameters of a shared continent link: the open-world filter set */
    private const array CONTINENT_PARAMETERS = [
        'type'         => 'npc_death',
        'region'       => 'eu',
        'minItemLevel' => '100',
        'maxItemLevel' => '300',
        'minPeriod'    => '1080',
        'maxPeriod'    => '1082',
    ];

    /** Query parameters outside the open-world filter set */
    private const array MYTHIC_PLUS_PARAMETERS = [
        'minMythicLevel'        => '10',
        'maxMythicLevel'        => '12',
        'minPlayerDeaths'       => '0',
        'maxPlayerDeaths'       => '3',
        'minTimerFraction'      => '0.5',
        'maxTimerFraction'      => '1',
        'includeAffixIds'       => '9',
        'includeClassIds'       => '1',
        'excludeSpecIds'        => '2',
        'includePlayerSpellIds' => '3',
        'season'                => 'season-mn-1',
    ];

    #[Override]
    protected function tearDown(): void
    {
        try {
            Carbon::setTestNow();
        } finally {
            parent::tearDown();
        }
    }

    #[Test]
    #[DataProvider('continentProvider')]
    public function viewDungeonFloor_givenContinent_rendersOnlyTheOpenWorldFilters(DungeonKey $dungeonKey): void
    {
        // Arrange
        [$dungeon, $gameVersion, $floor] = $this->getContinent($dungeonKey);

        // Act
        $response = $this->get(route('dungeon.heatmap.gameversion.view.floor', [
            'gameVersion' => $gameVersion,
            'dungeon'     => $dungeon,
            'floorIndex'  => $floor->index,
        ]));

        // Assert
        $response->assertOk();
        foreach (self::CONTINENT_FILTER_IDS as $id) {
            $response->assertSee(sprintf('id="%s"', $id), false);
        }
        foreach (self::MYTHIC_PLUS_FILTER_IDS as $id) {
            $response->assertDontSee(sprintf('id="%s"', $id), false);
        }
        $response->assertSee(sprintf('id="filter_event_type_%s"', CombatLogEventEventType::PlayerDeath->value), false);
        $response->assertSee(sprintf('id="filter_event_type_%s"', CombatLogEventEventType::NpcDeath->value), false);
        $response->assertDontSee(sprintf('id="filter_event_type_%s"', CombatLogEventEventType::PlayerSpell->value), false);
    }

    #[Test]
    public function viewDungeonFloor_givenDungeon_rendersTheMythicPlusFilters(): void
    {
        // Arrange
        [$dungeon, $gameVersion, $floor] = $this->getHeatmapDungeon();

        // Act
        $response = $this->get(route('dungeon.heatmap.gameversion.view.floor', [
            'gameVersion' => $gameVersion,
            'dungeon'     => $dungeon,
            'floorIndex'  => $floor->index,
        ]));

        // Assert
        $response->assertOk();
        foreach (self::MYTHIC_PLUS_FILTER_IDS as $id) {
            $response->assertSee(sprintf('id="%s"', $id), false);
        }
        $response->assertSee(sprintf('id="filter_event_type_%s"', CombatLogEventEventType::PlayerSpell->value), false);
        $response->assertDontSee('id="filter_weeks"', false);
    }

    #[Test]
    public function viewDungeonFloor_givenContinent_offersTheMostRecentWeeksByTheirStartDate(): void
    {
        // Arrange - the default region is the US, whose weekly reset is Tuesday 15:00 UTC
        Carbon::setTestNow(Carbon::parse('2026-11-10 16:00:00', 'UTC'));
        config(['keystoneguru.heatmap.continent.week_count' => 3]);
        [$dungeon, $gameVersion, $floor] = $this->getContinent(DungeonKey::KALIMDOR);

        // Act
        $response = $this->get(route('dungeon.heatmap.gameversion.view.floor', [
            'gameVersion' => $gameVersion,
            'dungeon'     => $dungeon,
            'floorIndex'  => $floor->index,
        ]));

        // Assert
        $response->assertOk();
        $response->assertViewHas('continentWeeks', static fn(Collection $weeks) => $weeks->count() === 3);
        $response->assertSeeInOrder([
            'id="filter_weeks"',
            __('view_common.maps.controls.heatmapsearch.week_option', ['date' => '2026-10-27']),
            __('view_common.maps.controls.heatmapsearch.week_option', ['date' => '2026-11-03']),
            __('view_common.maps.controls.heatmapsearch.week_option', ['date' => '2026-11-10']),
        ], false);
        $response->assertDontSee(__('view_common.maps.controls.heatmapsearch.week_option', ['date' => '2026-10-20']), false);
    }

    #[Test]
    #[DataProvider('continentProvider')]
    public function viewDungeonFloor_givenContinentLinkToAMissingFloor_redirectsWithOnlyTheOpenWorldFilters(DungeonKey $dungeonKey): void
    {
        // Arrange
        [$dungeon, $gameVersion] = $this->getContinent($dungeonKey);

        // Act
        $response = $this->get(route('dungeon.heatmap.gameversion.view.floor', [
            'gameVersion' => $gameVersion,
            'dungeon'     => $dungeon,
            'floorIndex'  => 999999,
        ] + self::CONTINENT_PARAMETERS + self::MYTHIC_PLUS_PARAMETERS));

        // Assert
        $response->assertRedirect();
        parse_str((string)parse_url($response->headers->get('Location'), PHP_URL_QUERY), $redirectParameters);
        $this->assertSame(self::CONTINENT_PARAMETERS, $redirectParameters);
    }

    #[Test]
    public function viewDungeonFloor_givenDungeonLinkToAMissingFloor_redirectsWithEveryFilter(): void
    {
        // Arrange
        [$dungeon, $gameVersion] = $this->getHeatmapDungeon();
        $parameters = array_merge(self::CONTINENT_PARAMETERS, self::MYTHIC_PLUS_PARAMETERS, ['type' => CombatLogEventEventType::PlayerSpell->value]);
        unset($parameters['season'], $parameters['includePlayerSpellIds']);

        // Act
        $response = $this->get(route('dungeon.heatmap.gameversion.view.floor', [
            'gameVersion' => $gameVersion,
            'dungeon'     => $dungeon,
            'floorIndex'  => 999999,
        ] + $parameters));

        // Assert
        $response->assertRedirect();
        parse_str((string)parse_url($response->headers->get('Location'), PHP_URL_QUERY), $redirectParameters);
        $this->assertEqualsCanonicalizing($parameters, $redirectParameters);
    }

    #[Test]
    #[DataProvider('continentProvider')]
    public function embed_givenContinentWithParametersOutsideTheOpenWorldFilters_passesOnlyTheOpenWorldFilters(DungeonKey $dungeonKey): void
    {
        // Arrange
        [$dungeon, $gameVersion, $floor] = $this->getContinent($dungeonKey);

        // Act
        $response = $this->get(route('dungeon.heatmap.gameversion.embed.floor', [
            'gameVersion' => $gameVersion,
            'dungeon'     => $dungeon,
            'floorIndex'  => $floor->index,
        ] + self::CONTINENT_PARAMETERS + self::MYTHIC_PLUS_PARAMETERS));

        // Assert
        $response->assertOk();
        $response->assertViewHas('parameters', static fn(array $parameters) => $parameters === self::CONTINENT_PARAMETERS);
    }

    /**
     * @return array<string, array{0: DungeonKey}>
     */
    public static function continentProvider(): array
    {
        return [
            'kalimdor'         => [DungeonKey::KALIMDOR],
            'eastern kingdoms' => [DungeonKey::EASTERN_KINGDOMS],
        ];
    }

    #[Test]
    public function viewDungeonFloor_givenContinentAndPlayerSpellEventType_failsValidation(): void
    {
        // Arrange
        [$dungeon, $gameVersion, $floor] = $this->getContinent(DungeonKey::KALIMDOR);

        // Act
        $response = $this->get(route('dungeon.heatmap.gameversion.view.floor', [
            'gameVersion' => $gameVersion,
            'dungeon'     => $dungeon,
            'floorIndex'  => $floor->index,
            'type'        => CombatLogEventEventType::PlayerSpell->value,
        ]));

        // Assert
        $response->assertSessionHasErrors(['type']);
    }

    #[Test]
    public function viewDungeonFloor_givenContinentAndNpcDeathEventType_rendersTheHeatmap(): void
    {
        // Arrange
        [$dungeon, $gameVersion, $floor] = $this->getContinent(DungeonKey::KALIMDOR);

        // Act
        $response = $this->get(route('dungeon.heatmap.gameversion.view.floor', [
            'gameVersion' => $gameVersion,
            'dungeon'     => $dungeon,
            'floorIndex'  => $floor->index,
            'type'        => CombatLogEventEventType::NpcDeath->value,
        ]));

        // Assert
        $response->assertOk();
    }

    /**
     * @return array{0: Dungeon, 1: GameVersion, 2: Floor}
     */
    private function getContinent(DungeonKey $dungeonKey): array
    {
        $dungeon = Dungeon::where('key', $dungeonKey->value)->firstOrFail();
        /** @var Floor $facadeFloor */
        $facadeFloor = $dungeon->floors()->where('facade', true)->firstOrFail();

        return [$dungeon, $dungeon->getCurrentMappingVersion()->gameVersion, $facadeFloor];
    }

    /**
     * @return array{0: Dungeon, 1: GameVersion, 2: Floor}
     */
    private function getHeatmapDungeon(): array
    {
        [$dungeon, $mappingVersion] = $this->findDungeon(
            facadeEnabled:       false,
            challengeMode:       true,
            dungeonActive:       true,
            requireDefaultFloor: true,
            constraint:          static fn(Builder $query) => $query->where('heatmap_enabled', 1),
        );
        /** @var Floor $floor */
        $floor = Floor::where('dungeon_id', $dungeon->id)->defaultOrFacade($mappingVersion)->firstOrFail();

        return [$dungeon, $mappingVersion->gameVersion, $floor];
    }
}
