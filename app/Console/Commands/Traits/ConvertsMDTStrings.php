<?php

namespace App\Console\Commands\Traits;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

/**
 * @mixin Command
 */
trait ConvertsMDTStrings
{

    /** @var string The location to save files - this currently uses the memfs so it's super fast */
    private static string $TMP_FILE_BASE_DIR = '/dev/shm/keystone.guru/mdt/';

    /** @var string */
    private static string $CLI_PARSER_ENCODE_CMD = 'cli_weakauras_parser encode %s';

    /** @var string */
    private static string $CLI_PARSER_DECODE_CMD = 'cli_weakauras_parser decode %s';

    /**
     * Checks if we should log a string to the error logger should it fail parsing
     *
     * @param string $string
     * @return bool
     * @see https://stackoverflow.com/a/34982057/771270
     */
    private function shouldErrorLog(string $string): bool
    {
        // Check if it's a base64 encoded string - ish
        return (bool)preg_match('%^![a-zA-Z0-9/+()]*={0,2}$%', $string);
    }

    /**
     * @param string $string
     * @return string|null
     */
    private function saveFile(string $string): ?string
    {
        $result = null;

        // Make sure the dir exists
        if (file_exists(self::$TMP_FILE_BASE_DIR) || mkdir(self::$TMP_FILE_BASE_DIR, 0777, true)) {

            do {
                // Generate a file name
                $fileName = sprintf('%s%d', self::$TMP_FILE_BASE_DIR, rand());
            } while (file_exists($fileName));

            // Save to disk
            if (file_put_contents($fileName, $string)) {
                $result = $fileName;
            }
        }

        return $result;
    }

    /**
     * @param bool $encode True to encode, false to decode it.
     * @param string $string The string you want to encode/decode.
     * @return string|null
     */
    private function transform(bool $encode, string $string): ?string
    {
        $result = null;

        // Save a temp file so that the parser can handle it
        try {
            $fileName = $this->saveFile($string);

            if ($fileName !== null) {
                $cmd = sprintf($encode ? self::$CLI_PARSER_ENCODE_CMD : self::$CLI_PARSER_DECODE_CMD, $fileName);
                $process = new Process(explode(' ', $cmd));
                $process->run();


                // executes after the command finishes
                if (!$process->isSuccessful()) {
                    $errorOutput = trim($process->getErrorOutput());

                    // Give output to the artisan command
                    $this->error($errorOutput);

                    // Only interested in decode - we're really only interested if it was a encoded, which would indicate some issue
                    // with either the string or a new format the tool I use can't handle. We don't care for things that aren't
                    // MDT strings - they should be ignored
                    if (!$encode && $this->shouldErrorLog($string)) {
                        logger()->error($errorOutput, [
                            'string' => $string
                        ]);
                    }
                }

                $result = $process->getOutput();
            }

        } finally {
            if ($fileName !== null) {
                unlink($fileName);
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
        return $this->transform(true, $string);
    }

    /**
     * @param string $string
     * @return string
     */
    private function decode(string $string): string
    {
        return $this->transform(false, $string);
    }
}