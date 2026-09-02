<?php

namespace App\Service\WagoTools;

use App\Service\WagoTools\Exceptions\WagoToolsDownloadException;
use App\Service\WagoTools\Logging\WagoToolsServiceLoggingInterface;
use Generator;

class WagoToolsService implements WagoToolsServiceInterface
{
    private const string BUILDS_URL = 'https://wago.tools/api/builds';

    private const string TABLE_CSV_URL = 'https://wago.tools/db2/%s/csv?build=%s&locale=%s';

    /** DB2 `_lang` columns are per-locale; we only ever render the English descriptions. */
    private const string LOCALE = 'enUS';

    /** Only `Interface\ICONS\` entries are addressable on Wowhead's icon CDN. */
    private const string ICONS_FILE_PATH = 'interface\icons\\';

    /**
     * A build is four dot separated numbers, e.g. `12.1.0.69214`. Both the CLI and wago.tools' own API
     * feed this string, and it ends up in a filesystem path - so anything else is refused rather than
     * allowed to walk out of the storage directory.
     */
    private const string BUILD_PATTERN = '/^\d+(\.\d+){3}$/';

    public function __construct(
        private readonly WagoToolsServiceLoggingInterface $log,
    ) {
    }

    public function getLatestBuild(string $product): ?string
    {
        $response = $this->curlGetContents(self::BUILDS_URL);

        if ($response === null) {
            $this->log->getLatestBuildRequestFailed($product);

            return null;
        }

        $builds = json_decode($response, true);

        if (!is_array($builds) || !isset($builds[$product]) || !is_array($builds[$product])) {
            $this->log->getLatestBuildUnknownProduct($product);

            return null;
        }

        // wago.tools returns the builds of a product newest first
        $latestBuild = $builds[$product][0]['version'] ?? null;

        if (!is_string($latestBuild) || preg_match(self::BUILD_PATTERN, $latestBuild) !== 1) {
            $this->log->getLatestBuildInvalidResponse($product);

            return null;
        }

        return $latestBuild;
    }

    public function getTableCsvPath(string $table, string $build): string
    {
        if (preg_match(self::BUILD_PATTERN, $build) !== 1) {
            throw new WagoToolsDownloadException(sprintf('%s is not a game build', $build));
        }

        if (preg_match('/^[A-Za-z]+$/', $table) !== 1) {
            throw new WagoToolsDownloadException(sprintf('%s is not a DB2 table', $table));
        }

        $targetFile = $this->getTableCsvTargetPath($table, $build);

        if (file_exists($targetFile) && filesize($targetFile) > 0) {
            $this->log->getTableCsvPathCacheHit($table, $build);

            return $targetFile;
        }

        $targetDirectory = dirname($targetFile);
        if (!is_dir($targetDirectory) && !mkdir($targetDirectory, 0755, true) && !is_dir($targetDirectory)) {
            throw new WagoToolsDownloadException(sprintf('Unable to create directory %s', $targetDirectory));
        }

        // Downloaded to a temporary file first - an interrupted download must not leave a truncated CSV
        // behind that every subsequent run then happily reads from its cache.
        $temporaryFile = sprintf('%s.download', $targetFile);

        try {
            $this->log->downloadTableStart($table, $build);

            if (!$this->curlDownloadToFile(sprintf(self::TABLE_CSV_URL, $table, $build, self::LOCALE), $temporaryFile)) {
                throw new WagoToolsDownloadException(sprintf('Unable to download DB2 table %s for build %s', $table, $build));
            }

            if (!rename($temporaryFile, $targetFile)) {
                throw new WagoToolsDownloadException(sprintf('Unable to move downloaded DB2 table %s into place', $table));
            }
        } finally {
            if (file_exists($temporaryFile)) {
                unlink($temporaryFile);
            }

            $this->log->downloadTableEnd();
        }

        return $targetFile;
    }

    public function readTable(string $table, string $build): Generator
    {
        $handle = fopen($this->getTableCsvPath($table, $build), 'r');

        if ($handle === false) {
            throw new WagoToolsDownloadException(sprintf('Unable to open DB2 table %s for build %s', $table, $build));
        }

        try {
            $header = fgetcsv($handle, escape: '');

            if (!is_array($header)) {
                throw new WagoToolsDownloadException(sprintf('DB2 table %s for build %s is empty', $table, $build));
            }

            $columnCount = count($header);

            while (($row = fgetcsv($handle, escape: '')) !== false) {
                // A row with a different column count cannot be keyed by header; DB2 CSVs are machine
                // generated so this only happens on a corrupt download.
                if (count($row) !== $columnCount) {
                    continue;
                }

                yield array_combine($header, $row);
            }
        } finally {
            fclose($handle);
        }
    }

    public function getIconFileNamesByFileDataIds(array $fileDataIds, string $build): array
    {
        try {
            $this->log->getIconFileNamesByFileDataIdsStart(count($fileDataIds));

            if ($fileDataIds === []) {
                return [];
            }

            // Keyed so that the per-row lookup below stays O(1) - the DB2 holds hundreds of thousands of rows
            $wantedFileDataIds = array_flip($fileDataIds);
            $result            = [];

            foreach ($this->readTable('ManifestInterfaceData', $build) as $row) {
                $fileDataId = (int)($row['ID'] ?? 0);

                if (!isset($wantedFileDataIds[$fileDataId])) {
                    continue;
                }

                // The DB2 is inconsistently cased - `Interface\ICONS\` and `interface\ICONS\` both occur
                if (strtolower($row['FilePath'] ?? '') !== self::ICONS_FILE_PATH) {
                    continue;
                }

                $result[$fileDataId] = strtolower(pathinfo($row['FileName'] ?? '', PATHINFO_FILENAME));
            }

            foreach ($fileDataIds as $fileDataId) {
                if (!isset($result[$fileDataId])) {
                    $this->log->getIconFileNamesByFileDataIdsNotFound($fileDataId);
                }
            }

            return $result;
        } finally {
            $this->log->getIconFileNamesByFileDataIdsEnd();
        }
    }

    private function getTableCsvTargetPath(string $table, string $build): string
    {
        return storage_path(sprintf('app/db2/%s/%s.csv', $build, $table));
    }

    /**
     * Streams the response straight to disk - these CSVs run to tens of megabytes, and the shared Curl
     * trait buffers the entire body in memory.
     */
    private function curlDownloadToFile(string $url, string $targetFile): bool
    {
        $fileHandle = fopen($targetFile, 'w');

        if ($fileHandle === false) {
            return false;
        }

        $curlHandle = curl_init();

        curl_setopt_array($curlHandle, [
            CURLOPT_URL            => $url,
            CURLOPT_FILE           => $fileHandle,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_ENCODING       => '',
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_TIMEOUT        => 600,
        ]);

        try {
            $success  = curl_exec($curlHandle) !== false;
            $httpCode = (int)curl_getinfo($curlHandle, CURLINFO_RESPONSE_CODE);

            if (!$success || $httpCode < 200 || $httpCode >= 300) {
                $this->log->downloadTableFailed($url, $httpCode, curl_error($curlHandle));

                return false;
            }
        } finally {
            curl_close($curlHandle);
            fclose($fileHandle);
        }

        return true;
    }

    private function curlGetContents(string $url): ?string
    {
        $curlHandle = curl_init();

        curl_setopt_array($curlHandle, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_ENCODING       => '',
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_TIMEOUT        => 60,
        ]);

        try {
            $response = curl_exec($curlHandle);
            $httpCode = (int)curl_getinfo($curlHandle, CURLINFO_RESPONSE_CODE);

            if (!is_string($response) || $httpCode < 200 || $httpCode >= 300) {
                return null;
            }
        } finally {
            curl_close($curlHandle);
        }

        return $response;
    }
}
