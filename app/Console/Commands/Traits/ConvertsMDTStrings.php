<?php

namespace App\Console\Commands\Traits;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

/**
 * @mixin Command
 */
trait ConvertsMDTStrings
{
    private static string $SUDO = '/usr/bin/sudo';

    private static string $CLI_PARSER_ENCODE_CMD = '/usr/bin/cli_weakauras_parser encode %s';

    private static string $CLI_PARSER_DECODE_CMD = '/usr/bin/cli_weakauras_parser decode %s';

    /**
     * Checks if we should log a string to the error logger should it fail parsing
     *
     * @see https://stackoverflow.com/a/34982057/771270
     */
    private function shouldErrorLog(string $string): bool
    {
        // Check if it's a base64 encoded string - ish
        return (bool)preg_match('%^![a-zA-Z0-9/+()]*={0,2}$%', $string);
    }

    /**
     * @param bool   $encode True to encode, false to decode it.
     * @param string $string The string you want to encode/decode.
     */
    private function transform(bool $encode, string $string): ?string
    {
        $result   = null;
        $fileName = null;

        try {
            $tmpFile = tempnam(sys_get_temp_dir(), 'ksg_mdt_');

            if ($tmpFile !== false) {
                $fileName = $tmpFile;
                file_put_contents($fileName, $string);

                $cmd = sprintf($encode ? self::$CLI_PARSER_ENCODE_CMD : self::$CLI_PARSER_DECODE_CMD, $fileName);
                $cmd = sprintf('%s %s', self::$SUDO, $cmd);

                $process = new Process(explode(' ', $cmd));
                $process->run();

                // executes after the command finishes
                if (!$process->isSuccessful()) {
                    $errorOutput = trim($process->getErrorOutput());

                    // Give output to the artisan command
                    $this->error($errorOutput);

                    // Only interested in decode - we're really only interested if it wasn't encoded, which would indicate some issue
                    // with either the string or a new format the tool I use can't handle. We don't care for things that aren't
                    // MDT strings - they should be ignored
                    if (!$encode && $this->shouldErrorLog($string)) {
                        logger()->error($errorOutput, [
                            'string' => $string,
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

    protected function encode(string $string): ?string
    {
        return $this->transform(true, $string);
    }

    protected function decode(string $string): string
    {
        return $this->transform(false, $string);
    }
}
