<?php

namespace Tests\Feature\Console\Commands\Localization;

use App\Console\Commands\Localization\Zone\SyncZoneNames;
use App\Models\GameVersion\GameVersion;
use App\Service\Wowhead\WowheadTranslationServiceInterface;
use Illuminate\Console\OutputStyle;
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
