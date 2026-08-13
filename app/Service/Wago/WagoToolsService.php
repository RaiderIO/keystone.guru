<?php

namespace App\Service\Wago;

use App\Service\Traits\Curl;
use App\Service\Wago\Logging\WagoToolsServiceLoggingInterface;

class WagoToolsService implements WagoToolsServiceInterface
{
    use Curl;

    /**
     * The DB2 that maps every interface file's FileDataID to its path and file name. It is the only public
     * source that turns MDT's `info.texture` into something downloadable - roughly 9MB of CSV, which is why
     * it is fetched once per {@see WagoToolsService::getIconFileNamesByFileDataIds()} call and streamed
     * rather than resolved per FileDataID.
     */
    private const string MANIFEST_INTERFACE_DATA_CSV_URL = 'https://wago.tools/db2/ManifestInterfaceData/csv';

    /** Only `Interface\ICONS\` entries are addressable on Wowhead's icon CDN. */
    private const string ICONS_FILE_PATH = 'interface\icons\\';

    public function __construct(
        private readonly WagoToolsServiceLoggingInterface $log,
    ) {
    }

    public function getIconFileNamesByFileDataIds(array $fileDataIds): array
    {
        try {
            $this->log->getIconFileNamesByFileDataIdsStart(count($fileDataIds));

            if ($fileDataIds === []) {
                return [];
            }

            $csvFilePath = tempnam(sys_get_temp_dir(), 'manifestinterfacedata');
            if ($csvFilePath === false) {
                return [];
            }

            try {
                if (!$this->curlSaveToFile(self::MANIFEST_INTERFACE_DATA_CSV_URL, $csvFilePath)) {
                    $this->log->getIconFileNamesByFileDataIdsDownloadFailed(self::MANIFEST_INTERFACE_DATA_CSV_URL);

                    return [];
                }

                $result = $this->parseIconFileNames($csvFilePath, $fileDataIds);
            } finally {
                unlink($csvFilePath);
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

    /**
     * @param  array<int>         $fileDataIds
     * @return array<int, string>
     */
    private function parseIconFileNames(string $csvFilePath, array $fileDataIds): array
    {
        $handle = fopen($csvFilePath, 'r');
        if ($handle === false) {
            $this->log->getIconFileNamesByFileDataIdsUnableToOpenFile($csvFilePath);

            return [];
        }

        // Keyed so that the per-row lookup below stays O(1) - the CSV holds hundreds of thousands of rows
        $wantedFileDataIds = array_flip($fileDataIds);
        $result            = [];

        try {
            // Skip the header row (ID,FilePath,FileName)
            fgetcsv($handle, escape: '');

            while (($row = fgetcsv($handle, escape: '')) !== false) {
                if (count($row) < 3) {
                    continue;
                }

                [$fileDataId, $filePath, $fileName] = $row;

                if (!isset($wantedFileDataIds[(int)$fileDataId])) {
                    continue;
                }

                // The DB2 is inconsistently cased - `Interface\ICONS\` and `interface\ICONS\` both occur
                if (strtolower((string)$filePath) !== self::ICONS_FILE_PATH) {
                    continue;
                }

                $result[(int)$fileDataId] = strtolower(pathinfo((string)$fileName, PATHINFO_FILENAME));
            }
        } finally {
            fclose($handle);
        }

        return $result;
    }
}
