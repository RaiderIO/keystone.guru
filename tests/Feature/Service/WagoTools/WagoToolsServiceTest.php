<?php

namespace Tests\Feature\Service\WagoTools;

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

    private function writeTable(string $table, string $contents): void
    {
        $directory = $this->getDb2Directory();

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
        foreach (glob(sprintf('%s/*.csv', $this->getDb2Directory())) ?: [] as $filePath) {
            unlink($filePath);
        }

        if (is_dir($this->getDb2Directory())) {
            rmdir($this->getDb2Directory());
        }
    }

    private function getDb2Directory(): string
    {
        return storage_path(sprintf('app/db2/%s', self::BUILD));
    }
}
