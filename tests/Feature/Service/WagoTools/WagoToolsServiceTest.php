<?php

namespace Tests\Feature\Service\WagoTools;

use App\Service\WagoTools\Exceptions\WagoToolsDownloadException;
use App\Service\WagoTools\GameLocale;
use App\Service\WagoTools\Logging\WagoToolsServiceLoggingInterface;
use App\Service\WagoTools\WagoToolsService;
use App\Service\WagoTools\WagoToolsServiceInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('SpellDescription')]
final class WagoToolsServiceTest extends PublicTestCase
{
    private const string BUILD = '0.0.0.00001';

    #[Test]
    public function readTable_givenADownloadedTable_yieldsRowsKeyedByColumn(): void
    {
        // Arrange
        try {
            $this->writeTable('SpellDuration', <<<'CSV'
                ID,Duration,MaxDuration
                1,10000,10000
                3,"60,000",60000
                CSV);

            /** @var WagoToolsServiceInterface $wagoToolsService */
            $wagoToolsService = app(WagoToolsServiceInterface::class);

            // Act
            $rows = iterator_to_array($wagoToolsService->readTable('SpellDuration', self::BUILD));

            // Assert
            $this->assertCount(2, $rows);
            $this->assertSame(['ID' => '1', 'Duration' => '10000', 'MaxDuration' => '10000'], $rows[0]);
            // A quoted value keeps the comma inside it
            $this->assertSame('60,000', $rows[1]['Duration']);
        } finally {
            $this->removeTables();
        }
    }

    #[Test]
    public function readTable_givenARowThatDoesNotMatchTheHeader_skipsThatRow(): void
    {
        // Arrange
        try {
            $this->writeTable('SpellDuration', <<<'CSV'
                ID,Duration,MaxDuration
                1,10000,10000
                2,10000
                3,10000,10000
                CSV);

            /** @var WagoToolsServiceInterface $wagoToolsService */
            $wagoToolsService = app(WagoToolsServiceInterface::class);

            // Act
            $rows = iterator_to_array($wagoToolsService->readTable('SpellDuration', self::BUILD));

            // Assert - a row that cannot be keyed by the header comes from a corrupt download
            $this->assertCount(2, $rows);
            $this->assertSame(['1', '3'], array_column($rows, 'ID'));
        } finally {
            $this->removeTables();
        }
    }

    #[Test]
    public function getIconFileNamesByFileDataIds_givenWantedIconEntries_returnsThemLowercasedAndWithoutExtension(): void
    {
        // Arrange
        try {
            $this->writeTable('ManifestInterfaceData', <<<'CSV'
                ID,FilePath,FileName
                1,Interface\ICONS\,UI_Profession_Engineering.blp
                2,interface\icons\,UI_EquipmentSet.blp
                3,Interface\FrameXML\,Whatever.lua
                CSV);

            /** @var WagoToolsServiceInterface $wagoToolsService */
            $wagoToolsService = app(WagoToolsServiceInterface::class);

            // Act
            $result = $wagoToolsService->getIconFileNamesByFileDataIds([1, 2, 3, 4], self::BUILD);

            // Assert - FileDataID 3 is not an icon, FileDataID 4 is unknown, both are absent from the result
            $this->assertSame([
                1 => 'ui_profession_engineering',
                2 => 'ui_equipmentset',
            ], $result);
        } finally {
            $this->removeTables();
        }
    }

    #[Test]
    public function getIconFileNamesByFileDataIds_givenNoFileDataIds_returnsEmptyArrayWithoutDownloading(): void
    {
        /** @var WagoToolsServiceInterface $wagoToolsService */
        $wagoToolsService = app(WagoToolsServiceInterface::class);

        // Act
        $result = $wagoToolsService->getIconFileNamesByFileDataIds([], self::BUILD);

        // Assert
        $this->assertSame([], $result);
        $this->assertFileDoesNotExist(sprintf('%s/ManifestInterfaceData.csv', $this->getDb2Directory()));
    }

    #[Test]
    public function getIconFileNamesByFileDataIds_givenNoFileDataIds_doesNotReadTheTable(): void
    {
        // Arrange - a table already on disk, so reading it needs no download and only the log can tell
        $log = $this->createMockPublic(WagoToolsServiceLoggingInterface::class);
        $log->expects($this->never())->method('getTableCsvPathCacheHit');
        $log->expects($this->never())->method('downloadTableStart');

        try {
            $this->writeTable('ManifestInterfaceData', <<<'CSV'
                ID,FilePath,FileName
                1,Interface\ICONS\,UI_Profession_Engineering.blp
                CSV);

            // Act
            $result = (new WagoToolsService($log))->getIconFileNamesByFileDataIds([], self::BUILD);

            // Assert
            $this->assertSame([], $result);
        } finally {
            $this->removeTables();
        }
    }

    #[Test]
    public function getTableCsvPath_givenABuildThatIsNotAGameBuild_throwsWagoToolsDownloadException(): void
    {
        // Arrange - the build ends up in a filesystem path; a cached file sits where that path would point
        $build     = 'latest';
        $directory = storage_path(sprintf('app/db2/%s/%s', $build, GameLocale::English->value));
        mkdir($directory, 0755, true);
        file_put_contents(sprintf('%s/Spell.csv', $directory), "ID\n1");

        try {
            // Assert
            $this->expectException(WagoToolsDownloadException::class);
            $this->expectExceptionMessage('latest is not a game build');

            // Act
            app(WagoToolsServiceInterface::class)->getTableCsvPath('Spell', $build);
        } finally {
            unlink(sprintf('%s/Spell.csv', $directory));
            rmdir($directory);
            rmdir(dirname($directory));
        }
    }

    #[Test]
    public function getTableCsvPath_givenATableNameWithAPath_throwsWagoToolsDownloadException(): void
    {
        // Arrange - a cached file sits where the table name would walk out of the locale directory to
        $cachedFile = storage_path(sprintf('app/db2/%s/Spell.csv', self::BUILD));
        mkdir($this->getDb2Directory(), 0755, true);
        file_put_contents($cachedFile, "ID\n1");

        try {
            // Assert
            $this->expectException(WagoToolsDownloadException::class);
            $this->expectExceptionMessage('../Spell is not a DB2 table');

            // Act
            app(WagoToolsServiceInterface::class)->getTableCsvPath('../Spell', self::BUILD);
        } finally {
            unlink($cachedFile);
            $this->removeTables();
        }
    }

    #[Test]
    public function getLatestBuild_givenAVersionThatIsNotABuild_returnsNull(): void
    {
        // Arrange - the latest build becomes part of a filesystem path, so anything but a build is refused
        $wagoToolsService = $this->createServiceWithBuildsResponse(json_encode([
            'wow' => [['version' => '../../12.1.0.69404']],
        ]));

        // Act
        $latestBuild = $wagoToolsService->getLatestBuild('wow');

        // Assert
        $this->assertNull($latestBuild);
    }

    #[Test]
    public function getLatestBuild_givenBuildsResponse_returnsTheNewestBuildOfTheProduct(): void
    {
        // Arrange
        $wagoToolsService = $this->createServiceWithBuildsResponse($this->buildsResponse());

        // Act
        $latestBuild = $wagoToolsService->getLatestBuild('wow');

        // Assert
        $this->assertSame('12.1.0.69404', $latestBuild);
    }

    #[Test]
    public function getLatestBuild_givenFailedRequest_returnsNull(): void
    {
        // Arrange
        $wagoToolsService = $this->createServiceWithBuildsResponse(null);

        // Act
        $latestBuild = $wagoToolsService->getLatestBuild('wow');

        // Assert
        $this->assertNull($latestBuild);
    }

    #[Test]
    public function getBuildReleasedAt_givenKnownBuild_returnsItsCreatedAtInUtc(): void
    {
        // Arrange
        $wagoToolsService = $this->createServiceWithBuildsResponse($this->buildsResponse());

        // Act
        $releasedAt = $wagoToolsService->getBuildReleasedAt('wow', '12.1.0.69382');

        // Assert
        $this->assertNotNull($releasedAt);
        $this->assertSame('2026-08-18 00:59:01', $releasedAt->toDateTimeString());
        $this->assertSame('UTC', $releasedAt->getTimezone()->getName());
    }

    #[Test]
    public function getBuildReleasedAt_givenBuildOfAnotherProduct_returnsNull(): void
    {
        // Arrange - the build exists, but only on the PTR
        $wagoToolsService = $this->createServiceWithBuildsResponse($this->buildsResponse());

        // Act
        $releasedAt = $wagoToolsService->getBuildReleasedAt('wow', '12.2.0.70001');

        // Assert
        $this->assertNull($releasedAt);
    }

    #[Test]
    public function getBuildReleasedAt_givenUnknownProduct_returnsNull(): void
    {
        // Arrange
        $wagoToolsService = $this->createServiceWithBuildsResponse($this->buildsResponse());

        // Act
        $releasedAt = $wagoToolsService->getBuildReleasedAt('wow_unknown', '12.1.0.69382');

        // Assert
        $this->assertNull($releasedAt);
    }

    #[Test]
    public function getBuildReleasedAt_givenFailedRequest_returnsNull(): void
    {
        // Arrange
        $wagoToolsService = $this->createServiceWithBuildsResponse(null);

        // Act
        $releasedAt = $wagoToolsService->getBuildReleasedAt('wow', '12.1.0.69382');

        // Assert
        $this->assertNull($releasedAt);
    }

    #[Test]
    public function getBuildReleasedAt_givenResponseThatIsNotJson_returnsNull(): void
    {
        // Arrange
        $wagoToolsService = $this->createServiceWithBuildsResponse('<html>Service unavailable</html>');

        // Act
        $releasedAt = $wagoToolsService->getBuildReleasedAt('wow', '12.1.0.69382');

        // Assert
        $this->assertNull($releasedAt);
    }

    #[Test]
    public function getBuildReleasedAt_givenMalformedCreatedAt_returnsNull(): void
    {
        // Arrange
        $wagoToolsService = $this->createServiceWithBuildsResponse(json_encode([
            'wow' => [['version' => '12.1.0.69382', 'created_at' => 'yesterday']],
        ]));

        // Act
        $releasedAt = $wagoToolsService->getBuildReleasedAt('wow', '12.1.0.69382');

        // Assert
        $this->assertNull($releasedAt);
    }

    #[Test]
    public function getBuildReleasedAt_givenMissingCreatedAt_returnsNull(): void
    {
        // Arrange
        $wagoToolsService = $this->createServiceWithBuildsResponse(json_encode([
            'wow' => [['version' => '12.1.0.69382']],
        ]));

        // Act
        $releasedAt = $wagoToolsService->getBuildReleasedAt('wow', '12.1.0.69382');

        // Assert
        $this->assertNull($releasedAt);
    }

    /**
     * A service whose request for wago.tools' build list returns the given body, or fails when it is null.
     */
    private function createServiceWithBuildsResponse(?string $buildsResponse): WagoToolsServiceInterface
    {
        return new class(app(WagoToolsServiceLoggingInterface::class), $buildsResponse) extends WagoToolsService {
            public function __construct(
                WagoToolsServiceLoggingInterface $log,
                private readonly ?string         $buildsResponse,
            ) {
                parent::__construct($log);
            }

            #[\Override]
            protected function curlGetContents(string $url): ?string
            {
                return $this->buildsResponse;
            }
        };
    }

    /**
     * The shape of https://wago.tools/api/builds: every product's builds, newest first.
     */
    private function buildsResponse(): string
    {
        return json_encode([
            'wow' => [
                ['product' => 'wow', 'version' => '12.1.0.69404', 'created_at' => '2026-08-20 19:18:02', 'is_bgdl' => false],
                ['product' => 'wow', 'version' => '12.1.0.69382', 'created_at' => '2026-08-18 00:59:01', 'is_bgdl' => false],
            ],
            'wowt' => [
                ['product' => 'wowt', 'version' => '12.2.0.70001', 'created_at' => '2026-09-01 10:00:00', 'is_bgdl' => false],
            ],
        ]);
    }

    private function writeTable(string $table, string $contents, GameLocale $locale = GameLocale::English): void
    {
        $directory = $this->getDb2Directory($locale);

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        // Heredocs keep the indentation of the code they sit in, which a CSV cannot have
        file_put_contents(
            sprintf('%s/%s.csv', $directory, $table),
            implode("\n", array_map(trim(...), explode("\n", $contents))),
        );
    }

    private function removeTables(): void
    {
        foreach (GameLocale::cases() as $locale) {
            foreach (glob(sprintf('%s/*.csv', $this->getDb2Directory($locale))) ?: [] as $filePath) {
                unlink($filePath);
            }

            if (is_dir($this->getDb2Directory($locale))) {
                rmdir($this->getDb2Directory($locale));
            }
        }

        $buildDirectory = storage_path(sprintf('app/db2/%s', self::BUILD));

        if (is_dir($buildDirectory)) {
            rmdir($buildDirectory);
        }
    }

    private function getDb2Directory(GameLocale $locale = GameLocale::English): string
    {
        return storage_path(sprintf('app/db2/%s/%s', self::BUILD, $locale->value));
    }
}
