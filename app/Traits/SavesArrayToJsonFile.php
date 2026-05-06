<?php

namespace App\Traits;

use Exception;
use stdClass;

trait SavesArrayToJsonFile
{
    /**
     * @param  array|stdClass $dataArr
     * @param  string         $dir
     * @param  string         $filename
     * @throws Exception
     */
    protected function saveDataToJsonFile(mixed $dataArr, string $dir, string $filename): void
    {
        if (!file_exists($dir)) {
            mkdir($dir, 755, true);
        }

        $filePath = $dir . '/' . $filename;
        $file     = null;

        try {
            $file = fopen($filePath, 'w') or exit('Cannot create file');
            if (!fwrite($file, json_encode($dataArr, JSON_PRETTY_PRINT))) {
                throw new Exception(sprintf('Unable to create file %s', $filePath));
            }
        } finally {
            fclose($file);
        }
    }
}
