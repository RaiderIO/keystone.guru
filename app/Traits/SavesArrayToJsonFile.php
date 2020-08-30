<?php


namespace App\Traits;

trait SavesArrayToJsonFile
{

    /**
     * @param $dataArr array|\stdClass
     * @param $dir string
     * @param $filename string
     */
    protected function saveDataToJsonFile($dataArr, string $dir, string $filename)
    {
        if (!file_exists($dir)) {
            mkdir($dir, 755, true);
        }

        $filePath = $dir . '/' . $filename;
        $file = fopen($filePath, 'w') or die('Cannot create file');
        fwrite($file, json_encode($dataArr, JSON_PRETTY_PRINT));
        fclose($file);
    }
}