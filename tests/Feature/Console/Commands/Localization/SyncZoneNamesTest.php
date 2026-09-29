<?php

namespace Tests\Feature\Console\Commands\Localization;

use App\Console\Commands\Localization\Zone\SyncZoneNames;
use App\Models\Dungeon;
use App\Models\Floor\Floor;
use App\Models\GameVersion\GameVersion;
use App\Service\Wowhead\WowheadTranslationServiceInterface;
use Illuminate\Console\OutputStyle;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Tests\TestCases\PublicTestCase;

#[Group('Console')]
#[Group('Localization')]
final class SyncZoneNamesTest extends PublicTestCase
{
    #[Test]
    #[DataProvider('normalizeFloorName_givenSameFloorWrittenDifferently_returnsSameName_dataProvider')]
    public function normalizeFloorName_givenSameFloorWrittenDifferently_returnsSameName(string $wowheadName, string $ksgName): void
    {
        // Arrange
        $command = new SyncZoneNames();

        // Act
        $normalizedWowheadName = $command->normalizeFloorName($wowheadName);
        $normalizedKsgName     = $command->normalizeFloorName($ksgName);

        // Assert
        $this->assertSame($normalizedKsgName, $normalizedWowheadName);
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function normalizeFloorName_givenSameFloorWrittenDifferently_returnsSameName_dataProvider(): array
    {
        return [
            'dash'       => ["Vereesa's Repose - Upper", "Vereesa's Repose Upper"],
            'case'       => ["Atal'Dazar", "Atal'dazar"],
            'apostrophe' => ["Sylvanas's Quarters - Lower", 'Sylvanass Quarters Lower'],
        ];
    }

    #[Test]
    public function normalizeFloorName_givenDifferentFloors_returnsDifferentNames(): void
    {
        // Arrange
        $command = new SyncZoneNames();

        // Act
        $upper = $command->normalizeFloorName("Vereesa's Repose - Upper");
        $lower = $command->normalizeFloorName("Vereesa's Repose - Lower");

        // Assert
        $this->assertNotSame($upper, $lower);
    }

    #[Test]
    public function getDungeonsByZoneId_givenDungeonsWithoutZoneId_leavesThemOut(): void
    {
        // Arrange
        $command = new SyncZoneNames();

        // Act
        $dungeonsByZoneId = $command->getDungeonsByZoneId();

        // Assert
        $this->assertNotEmpty($dungeonsByZoneId);
        $this->assertFalse($dungeonsByZoneId->has(0));
        foreach ($dungeonsByZoneId as $zoneId => $dungeon) {
            $this->assertSame($zoneId, $dungeon->zone_id);
        }
    }

    #[Test]
    public function getGameDataIdsByKey_givenDungeonWithFloors_returnsMapZoneAndUiMapIds(): void
    {
        // Arrange
        $command = new SyncZoneNames();
        $dungeon = new Dungeon([
            'name'    => 'dungeons.tww.the_rookery.name',
            'map_id'  => 2648,
            'zone_id' => 14938,
        ]);
        $dungeon->setRelation('floors', new Collection([
            new Floor(['name' => 'dungeons.tww.the_rookery.floors.storms_roost', 'ui_map_id' => 2316, 'facade' => false]),
            new Floor(['name' => 'dungeons.tww.the_rookery.floors.the_rookery', 'ui_map_id' => 0, 'facade' => true]),
        ]));

        // Act
        $gameDataIdsByKey = $command->getGameDataIdsByKey(new Collection([$dungeon]));

        // Assert
        $this->assertSame([
            'tww.the_rookery.name'                => [[SyncZoneNames::SOURCE_MAP, 2648], [SyncZoneNames::SOURCE_AREA_TABLE, 14938]],
            'tww.the_rookery.floors.storms_roost' => [[SyncZoneNames::SOURCE_UI_MAP_GROUP_MEMBER, 2316], [SyncZoneNames::SOURCE_UI_MAP, 2316]],
            'tww.the_rookery.floors.the_rookery'  => [[SyncZoneNames::SOURCE_MAP, 2648], [SyncZoneNames::SOURCE_AREA_TABLE, 14938]],
        ], $gameDataIdsByKey);
    }

    #[Test]
    public function getGameDataIdsByKey_givenDungeonWithoutMapAndZone_returnsNoIdsForIt(): void
    {
        // Arrange
        $command = new SyncZoneNames();
        $dungeon = new Dungeon([
            'name'    => 'dungeons.bfa.orgrimmar_horrific_vision.name',
            'map_id'  => -1,
            'zone_id' => 0,
        ]);
        $dungeon->setRelation('floors', new Collection());

        // Act
        $gameDataIdsByKey = $command->getGameDataIdsByKey(new Collection([$dungeon]));

        // Assert
        $this->assertSame([], $gameDataIdsByKey);
    }

    #[Test]
    public function resolveGameDataNames_givenIdKnownForKey_prefersItOverTheSameNameElsewhere(): void
    {
        // Arrange
        $command = new SyncZoneNames();
        $sources = [
            SyncZoneNames::SOURCE_UI_MAP_GROUP_MEMBER => [
                'english'   => [617 => 'The Rookery'],
                'localized' => [617 => 'Der Horst'],
            ],
            SyncZoneNames::SOURCE_UI_MAP => [
                'english'   => [2315 => 'The Rookery'],
                'localized' => [2315 => 'Die Brutstätte'],
            ],
        ];

        // Act
        $result = $command->resolveGameDataNames(
            ['tww.the_rookery.name' => 'The Rookery'],
            ['tww.the_rookery.name' => [[SyncZoneNames::SOURCE_UI_MAP, 2315]]],
            $sources,
        );

        // Assert
        $this->assertSame(['tww.the_rookery.name' => 'Die Brutstätte'], $result);
    }

    #[Test]
    public function resolveGameDataNames_givenKnownIdWithOtherEnglishName_fallsBackToEnglishName(): void
    {
        // Arrange
        $command = new SyncZoneNames();
        $sources = [
            SyncZoneNames::SOURCE_UI_MAP => [
                'english'   => [339 => 'Black Temple'],
                'localized' => [339 => 'Der Schwarze Tempel'],
            ],
            SyncZoneNames::SOURCE_AREA_TABLE => [
                'english'   => [4009 => 'Illidari Training Grounds'],
                'localized' => [4009 => 'Ausbildungsgelände der Illidari'],
            ],
        ];

        // Act
        $result = $command->resolveGameDataNames(
            ['tbc.black_temple.floors.illidari_training_grounds' => 'Illidari Training Grounds'],
            ['tbc.black_temple.floors.illidari_training_grounds' => [[SyncZoneNames::SOURCE_UI_MAP, 339]]],
            $sources,
        );

        // Assert
        $this->assertSame(['tbc.black_temple.floors.illidari_training_grounds' => 'Ausbildungsgelände der Illidari'], $result);
    }

    #[Test]
    public function resolveGameDataNames_givenAmbiguousSource_usesTheNextSource(): void
    {
        // Arrange
        $command = new SyncZoneNames();
        $sources = [
            SyncZoneNames::SOURCE_UI_MAP => [
                'english'   => [13 => 'Eastern Kingdoms', 985 => 'Eastern Kingdoms'],
                'localized' => [13 => 'Östliche Königreiche', 985 => 'Die Östlichen Königreiche'],
            ],
            SyncZoneNames::SOURCE_MAP => [
                'english'   => [0 => 'Eastern Kingdoms'],
                'localized' => [0 => 'Die Östlichen Königreiche'],
            ],
        ];

        // Act
        $result = $command->resolveGameDataNames(['classic.eastern_kingdoms.name' => 'Eastern Kingdoms'], [], $sources);

        // Assert
        $this->assertSame(['classic.eastern_kingdoms.name' => 'Die Östlichen Königreiche'], $result);
    }

    #[Test]
    public function resolveGameDataNames_givenOnlyAmbiguousOrEmptyNames_leavesKeyOut(): void
    {
        // Arrange
        $command = new SyncZoneNames();
        $sources = [
            SyncZoneNames::SOURCE_UI_MAP => [
                'english'   => [13 => 'Eastern Kingdoms', 985 => 'Eastern Kingdoms', 2 => 'Riverglades'],
                'localized' => [13 => 'Östliche Königreiche', 985 => 'Die Östlichen Königreiche', 2 => ''],
            ],
        ];

        // Act
        $result = $command->resolveGameDataNames([
            'classic.eastern_kingdoms.name'               => 'Eastern Kingdoms',
            'classic.eastern_kingdoms.floors.riverglades' => 'Riverglades',
            'classic.kalimdor.floors.shendralas'          => "Shen'dralas",
        ], [], $sources);

        // Assert
        $this->assertSame([], $result);
    }

    #[Test]
    public function resolveGameDataNames_givenOwnWordingOfGameName_usesTheGameName(): void
    {
        // Arrange
        $command = new SyncZoneNames();
        $sources = [
            SyncZoneNames::SOURCE_MAP => [
                'english'   => [2212 => 'Horrific Vision of Orgrimmar'],
                'localized' => [2212 => 'Verstörende Vision von Orgrimmar'],
            ],
        ];

        // Act
        $result = $command->resolveGameDataNames(['bfa.orgrimmar_horrific_vision.name' => 'Orgrimmar (Horrific Vision)'], [], $sources);

        // Assert
        $this->assertSame(['bfa.orgrimmar_horrific_vision.name' => 'Verstörende Vision von Orgrimmar'], $result);
    }

    #[Test]
    public function resolveGameDataNames_givenRaidSize_usesTheGameDifficultyName(): void
    {
        // Arrange
        $command = new SyncZoneNames();
        $sources = [
            SyncZoneNames::SOURCE_DIFFICULTY => [
                'english'   => [3 => '10 Player'],
                'localized' => [3 => '10 игроков'],
            ],
        ];

        // Act
        $result = $command->resolveGameDataNames(['difficulty.1' => '10-man'], [], $sources);

        // Assert
        $this->assertSame(['difficulty.1' => '10 игроков'], $result);
    }

    #[Test]
    public function resolveGameDataNames_givenSameNameWithDifferentSpaces_treatsItAsOneName(): void
    {
        // Arrange
        $command = new SyncZoneNames();
        $sources = [
            SyncZoneNames::SOURCE_CLASSIC_ERA_DIFFICULTY => [
                'english'   => [148 => '20 Player', 185 => '20 Player'],
                'localized' => [148 => '20 joueurs', 185 => "20\u{00A0}joueurs"],
            ],
        ];

        // Act
        $result = $command->resolveGameDataNames(['difficulty.3' => '20-man'], [], $sources);

        // Assert
        $this->assertSame(['difficulty.3' => '20 joueurs'], $result);
    }

    #[Test]
    public function resolveGameDataNames_givenExistingNameTheGameUses_keepsIt(): void
    {
        // Arrange
        $command = new SyncZoneNames();
        $sources = [
            SyncZoneNames::SOURCE_MAP => [
                'english'   => [230 => 'Blackrock Depths'],
                'localized' => [230 => 'Schwarzfelstiefen'],
            ],
            SyncZoneNames::SOURCE_LFG_DUNGEONS => [
                'english'   => [30 => 'Blackrock Depths'],
                'localized' => [30 => 'Die Schwarzfelstiefen'],
            ],
        ];

        // Act
        $result = $command->resolveGameDataNames(
            ['classic.blackrock_depths.name' => 'Blackrock Depths'],
            ['classic.blackrock_depths.name' => [[SyncZoneNames::SOURCE_MAP, 230]]],
            $sources,
            ['classic.blackrock_depths.name' => 'Die Schwarzfelstiefen'],
        );

        // Assert
        $this->assertSame([], $result);
    }

    #[Test]
    public function resolveGameDataNames_givenExistingNameTheGameDoesNotUse_replacesIt(): void
    {
        // Arrange
        $command = new SyncZoneNames();
        $sources = [
            SyncZoneNames::SOURCE_MAP => [
                'english'   => [1492 => 'Maw of Souls'],
                'localized' => [1492 => 'Der Seelenschlund'],
            ],
        ];

        // Act
        $result = $command->resolveGameDataNames(
            ['legion.maw_of_souls.name' => 'Maw of Souls'],
            ['legion.maw_of_souls.name' => [[SyncZoneNames::SOURCE_MAP, 1492]]],
            $sources,
            ['legion.maw_of_souls.name' => 'Die Helmaulklippen'],
        );

        // Assert
        $this->assertSame(['legion.maw_of_souls.name' => 'Der Seelenschlund'], $result);
    }

    #[Test]
    public function isUntranslated_givenLocalizedNamesEqualToEnglish_returnsTrue(): void
    {
        // Arrange
        $command = new SyncZoneNames();

        // Act
        $isUntranslated = $command->isUntranslated(
            [18 => 'Scarlet Monastery - Graveyard', 34 => 'Dire Maul - East'],
            [18 => 'Scarlet Monastery - Graveyard', 34 => 'Dire Maul - East'],
        );

        // Assert
        $this->assertTrue($isUntranslated);
    }

    #[Test]
    public function isUntranslated_givenTranslatedNamesWithSomeEqualToEnglish_returnsFalse(): void
    {
        // Arrange
        $command = new SyncZoneNames();

        // Act
        $isUntranslated = $command->isUntranslated(
            [1 => 'Durotar', 2 => 'The Barrens', 3 => 'Elwynn Forest'],
            [1 => 'Durotar', 2 => 'Das Brachland', 3 => 'Wald von Elwynn'],
        );

        // Assert
        $this->assertFalse($isUntranslated);
    }

    #[Test]
    public function syncContinentNames_givenWowheadZoneNames_fillsContinentZoneFloors(): void
    {
        // Arrange
        $command = $this->createCommand();
        $service = $this->createWowheadTranslationService();

        // Act
        $result = $command->syncContinentNames($service, ['de_DE' => []]);

        // Assert
        $this->assertSame('Kalimdor', $result['de_DE']['classic']['kalimdor']['name']);
        $this->assertSame('Kalimdor', $result['de_DE']['classic']['kalimdor']['floors']['kalimdor']);
        $this->assertSame('Mulgore (de)', $result['de_DE']['classic']['kalimdor']['floors']['mulgore']);
        $this->assertSame('Brachland', $result['de_DE']['classic']['kalimdor']['floors']['the_barrens']);
        $this->assertSame('Östliche Königreiche', $result['de_DE']['classic']['eastern_kingdoms']['name']);
        $this->assertSame('Wald von Elwynn', $result['de_DE']['classic']['eastern_kingdoms']['floors']['elwynn_forest']);
        $this->assertArrayNotHasKey('tanaris', $result['de_DE']['classic']['kalimdor']['floors']);
    }

    #[Test]
    public function syncContinentNames_givenExistingName_keepsExistingName(): void
    {
        // Arrange
        $command  = $this->createCommand();
        $service  = $this->createWowheadTranslationService();
        $existing = ['de_DE' => ['classic' => ['kalimdor' => ['floors' => ['mulgore' => 'Mulgore (manuell)']]]]];

        // Act
        $result = $command->syncContinentNames($service, $existing);

        // Assert
        $this->assertSame('Mulgore (manuell)', $result['de_DE']['classic']['kalimdor']['floors']['mulgore']);
    }

    #[Test]
    public function mergeIntoAiTranslations_givenKeysMissingFromBaseLocale_keepsThemInAiLocale(): void
    {
        // Arrange
        $command = new SyncZoneNames();
        $english = ['classic' => ['kalimdor' => ['name' => 'Kalimdor', 'floors' => ['mulgore' => 'Mulgore', 'shendralas' => "Shen'dralas"]]]];
        $ai      = ['classic' => ['kalimdor' => ['name' => '', 'floors' => ['mulgore' => '']]]];
        $base    = ['classic' => ['deadmines' => ['name' => 'Die Todesminen']]];

        // Act
        $result = $command->mergeIntoAiTranslations($english, $ai, $base);

        // Assert
        $this->assertSame([
            'classic' => [
                'deadmines' => ['name' => 'Die Todesminen'],
                'kalimdor'  => ['name' => '', 'floors' => ['mulgore' => '', 'shendralas' => '']],
            ],
        ], $result);
    }

    #[Test]
    public function mergeIntoAiTranslations_givenEmptyBaseName_keepsAiName(): void
    {
        // Arrange
        $command = new SyncZoneNames();
        $ai      = ['tbc' => ['karazhan' => ['name' => 'Karazhan (KI)']]];
        $base    = ['tbc' => ['karazhan' => ['name' => '']]];

        // Act
        $result = $command->mergeIntoAiTranslations([], $ai, $base);

        // Assert
        $this->assertSame('Karazhan (KI)', $result['tbc']['karazhan']['name']);
    }

    private function createCommand(): SyncZoneNames
    {
        $command = new SyncZoneNames();
        $command->setOutput(new OutputStyle(new ArrayInput([]), new NullOutput()));

        return $command;
    }

    /**
     * Retail knows Mulgore and Elwynn Forest but only as "Northern Barrens" for zone 17; classic knows "The Barrens".
     * Nobody knows Tanaris.
     */
    private function createWowheadTranslationService(): WowheadTranslationServiceInterface
    {
        $service = $this->createMock(WowheadTranslationServiceInterface::class);
        $service->method('getContinentNames')->willReturn(collect([
            'en_US' => ['POSTMASTER_PIPE_KALIMDOR' => 'Kalimdor', 'POSTMASTER_PIPE_EASTERNKINGDOMS' => 'Eastern Kingdoms'],
            'de_DE' => ['POSTMASTER_PIPE_KALIMDOR' => 'Kalimdor', 'POSTMASTER_PIPE_EASTERNKINGDOMS' => 'Östliche Königreiche'],
        ]));
        $service->method('getZoneNames')->willReturnCallback(
            static fn(GameVersion $gameVersion) => $gameVersion->key === GameVersion::GAME_VERSION_RETAIL
                ? collect([
                    'en_US' => [12 => 'Elwynn Forest', 17 => 'Northern Barrens', 215 => 'Mulgore', 10397 => 'Mulgore'],
                    'de_DE' => [12 => 'Wald von Elwynn', 17 => 'Nördliches Brachland', 215 => 'Mulgore (de)', 10397 => 'Mulgore (Kopie)'],
                ])
                : collect([
                    'en_US' => [17 => 'The Barrens'],
                    'de_DE' => [17 => 'Brachland'],
                ]),
        );

        return $service;
    }
}
