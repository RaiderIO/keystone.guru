<?php

namespace Tests\Feature\Console\Commands\MDT;

use App\Console\Commands\MDT\ExportMapping;
use App\Models\Season;
use App\Service\MDT\MDTMappingExportServiceInterface;
use Mockery;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Console')]
#[Group('MDT')]
final class ExportMappingTest extends PublicTestCase
{
    /**
     * The MDT file names of every Midnight season 2 dungeon. They come from three of our expansions -
     * Ruby Life Pools is Dragonflight and King's Rest / Temple of Sethraliss are Battle for Azeroth - which
     * is exactly why selecting by expansion cannot express a season.
     */
    private const array SEASON_2_MDT_DUNGEON_NAMES = [
        'AltarOfFangs',
        'DenOfNalorakk',
        'MurderRow',
        'TheBlindingVale',
        'VoidscarArena',
        'RubyLifePools',
        'KingsRest',
        'TempleOfSethraliss',
    ];

    #[Test]
    public function handle_givenASeason_exportsOnlyThatSeasonsDungeonsAcrossExpansions(): void
    {
        // Arrange
        $targetFolder = $this->createTargetFolder();
        $this->mockExportService();

        try {
            // Act
            $this->artisan(ExportMapping::class, [
                'expansion'             => 'midnight',
                'gameVersion'           => 'retail',
                'targetFolder'          => $targetFolder,
                '--season'              => Season::SEASON_MIDNIGHT_S2,
                '--excludeTranslations' => true,
            ])->assertSuccessful();

            // Assert
            $writtenNames = $this->getWrittenDungeonNames($targetFolder);

            $this->assertNotEmpty($writtenNames, 'No dungeons were exported at all');
            $this->assertEmpty(
                array_diff($writtenNames, self::SEASON_2_MDT_DUNGEON_NAMES),
                'A dungeon outside of Midnight season 2 was exported',
            );
        } finally {
            $this->deleteTargetFolder($targetFolder);
        }
    }

    #[Test]
    public function handle_givenNoSeason_fallsBackToTheExpansionArgument(): void
    {
        // Arrange
        $targetFolder = $this->createTargetFolder();
        $this->mockExportService();

        try {
            // Act
            $this->artisan(ExportMapping::class, [
                'expansion'             => 'midnight',
                'gameVersion'           => 'retail',
                'targetFolder'          => $targetFolder,
                '--excludeTranslations' => true,
            ])->assertSuccessful();

            // Assert - the expansion holds season 1 dungeons that season 2 does not
            $writtenNames = $this->getWrittenDungeonNames($targetFolder);

            $this->assertContains('MurderRow', $writtenNames);
            $this->assertContains('MaisaraCaverns', $writtenNames);
        } finally {
            $this->deleteTargetFolder($targetFolder);
        }
    }

    #[Test]
    public function handle_givenAMissingTargetFolder_failsWithoutWritingAnything(): void
    {
        // Arrange
        $this->mockExportService(expectCalls: false);

        // Act & Assert - realpath() returns false here, which used to make the command write to /
        $this->artisan(ExportMapping::class, [
            'expansion'    => 'midnight',
            'gameVersion'  => 'retail',
            'targetFolder' => '/this/folder/does/not/exist',
        ])->assertFailed();
    }

    #[Test]
    public function handle_givenAMissingPreserveFolder_failsWithoutWritingAnything(): void
    {
        // Arrange
        $targetFolder = $this->createTargetFolder();
        $this->mockExportService(expectCalls: false);

        try {
            // Act & Assert
            $this->artisan(ExportMapping::class, [
                'expansion'      => 'midnight',
                'gameVersion'    => 'retail',
                'targetFolder'   => $targetFolder,
                '--preserveFrom' => '/this/folder/does/not/exist',
            ])->assertFailed();

            $this->assertSame([], $this->getWrittenDungeonNames($targetFolder));
        } finally {
            $this->deleteTargetFolder($targetFolder);
        }
    }

    #[Test]
    public function handle_givenAnUnknownGameVersion_fails(): void
    {
        // Arrange
        $targetFolder = $this->createTargetFolder();
        $this->mockExportService(expectCalls: false);

        try {
            // Act & Assert - this used to silently fall back to the default game version
            $this->artisan(ExportMapping::class, [
                'expansion'    => 'midnight',
                'gameVersion'  => 'not-a-game-version',
                'targetFolder' => $targetFolder,
            ])->assertFailed();
        } finally {
            $this->deleteTargetFolder($targetFolder);
        }
    }

    /**
     * Replaces the export service so these tests are about dungeon selection and file IO, not Lua content.
     */
    private function mockExportService(bool $expectCalls = true): void
    {
        $exportServiceMock = Mockery::mock(MDTMappingExportServiceInterface::class);

        if ($expectCalls) {
            $exportServiceMock->shouldReceive('getMDTMappingAsLuaString')->andReturn('-- exported');
        } else {
            $exportServiceMock->shouldNotReceive('getMDTMappingAsLuaString');
        }

        /** @var MDTMappingExportServiceInterface $exportService */
        $exportService = $exportServiceMock;
        app()->instance(MDTMappingExportServiceInterface::class, $exportService);
    }

    private function createTargetFolder(): string
    {
        $targetFolder = sprintf('%s/mdt-export-test-%s', sys_get_temp_dir(), uniqid());

        mkdir($targetFolder, 0o777, true);

        return $targetFolder;
    }

    /**
     * @return list<string>
     */
    private function getWrittenDungeonNames(string $targetFolder): array
    {
        return array_map(
            static fn(string $filePath) => basename($filePath, '.lua'),
            glob(sprintf('%s/*.lua', $targetFolder)) ?: [],
        );
    }

    private function deleteTargetFolder(string $targetFolder): void
    {
        foreach (glob(sprintf('%s/*.lua', $targetFolder)) ?: [] as $filePath) {
            unlink($filePath);
        }

        if (is_dir($targetFolder)) {
            rmdir($targetFolder);
        }
    }
}
