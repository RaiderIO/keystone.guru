<?php

namespace App\Traits;

trait SavesStringToTempDisk
{
    /** @var string The location to save files - this currently uses the memfs so it's super fast */
    private static string $TMP_FILE_BASE_DIR = '/dev/shm/keystone.guru';

    /**
     * @return string|null The resulting file name or
     */
    private function saveFile(string $subFolder, string $string): ?string
    {
        $result = null;

        $targetDir = sprintf('%s/%s', self::$TMP_FILE_BASE_DIR, $subFolder);

        // Make sure the dir exists
        if (file_exists($targetDir) || mkdir($targetDir, 0777, true)) {
            do {
                // Generate a file name
                $fileName = sprintf('%s/%d', $targetDir, random_int(0, mt_getrandmax()));
            } while (file_exists($fileName));

            // Save to disk
            if (file_put_contents($fileName, $string)) {
                $result = $fileName;
            }
        }

        return $result;
    }
}
