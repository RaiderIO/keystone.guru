<?php

namespace App\Console\Commands\Traits;

use Illuminate\Console\Command;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/**
 * @mixin Command
 */
trait ConvertsMDTStrings
{

    /** @var string The location to save files - this currently uses the memfs so it's super fast */
    private static $TMP_FILE_BASE_DIR = '/dev/shm/keystone.guru/mdt/';

    /** @var string */
    private static $CLI_PARSER_ENCODE_CMD = 'cli_weakauras_parser encode %s';

    /** @var string */
    private static $CLI_PARSER_DECODE_CMD = 'cli_weakauras_parser decode %s';

    /**
     * @param string $string
     * @return string|null
     */
    private function saveFile(string $string): ?string
    {
        $result = null;

        // Make sure the dir exists
        if (file_exists(self::$TMP_FILE_BASE_DIR) || mkdir(self::$TMP_FILE_BASE_DIR, 0777, true)) {
            // Generate a file name
            $fileName = sprintf('%s%d', self::$TMP_FILE_BASE_DIR, rand());

            // Save to disk
            if (file_put_contents($fileName, $string)) {
                $result = $fileName;
            }
        }

        return $result;
    }

    /**
     * @param string $string
     * @return string
     */
    private function encode(string $string): ?string
    {
        $result = null;

        // Save a temp file so that the parser can handle it
        $fileName = $this->saveFile(json_encode($string));

        if ($fileName !== null) {
            $process = new Process(explode(' ', sprintf(self::$CLI_PARSER_ENCODE_CMD, $fileName)));
            $process->run();

            // executes after the command finishes
            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }

            $result = $process->getOutput();
        }

        return $result;
    }

    private function decode(string $string): string
    {
        return '';
    }
}