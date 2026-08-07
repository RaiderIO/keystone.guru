<?php

namespace App\Logic\MDT\IO;

use App\Logic\MDT\Exception\CliWeakaurasParserNotFoundException;
use App\Logic\MDT\Exception\LegacyMDTDecodeException;
use App\Logic\MDT\Exception\LegacyMDTEncodeException;
use Symfony\Component\Process\Process;

/**
 * Codec for the legacy (pre-MDT 6.2) MDT export-string format: a `!`-prefixed, Base64-ish blob that
 * only `cli_weakauras_parser` (a Lua-backed CLI shim, see MDTTransmission.lua) knows how to
 * (de)serialize, so both directions shell out to it - unlike MDT2Codec, which decodes the newer
 * format natively in PHP.
 */
final class LegacyMDTCodec implements MDTStringCodecInterface
{
    private const string SUDO = '/usr/bin/sudo';

    private const string CLI_PARSER_ENCODE_CMD = '/usr/bin/cli_weakauras_parser encode %s';

    private const string CLI_PARSER_DECODE_CMD = '/usr/bin/cli_weakauras_parser decode %s';

    /**
     * Checks if $string is plausibly a legacy MDT export string - a `!`-prefixed Base64-ish blob.
     * MDT 6.2+ `!~MDT2~` strings are decoded natively by MDT2Codec and never reach here, so `~` is
     * deliberately not part of this character class.
     *
     * @see https://stackoverflow.com/a/34982057/771270
     */
    public function appliesTo(string $string): bool
    {
        return (bool)preg_match('%^![a-zA-Z0-9/+()]*={0,2}$%', trim($string));
    }

    /**
     * @throws CliWeakaurasParserNotFoundException
     * @throws LegacyMDTEncodeException
     */
    public function encode(array $contents): string
    {
        $json = json_encode($contents);

        if ($json === false) {
            throw new LegacyMDTEncodeException('Unable to JSON-encode the preset contents');
        }

        $output = $this->transform(true, $json);

        if (trim($output) === '') {
            throw new LegacyMDTEncodeException('cli_weakauras_parser produced no output while encoding');
        }

        return $output;
    }

    /**
     * @throws CliWeakaurasParserNotFoundException
     * @throws LegacyMDTDecodeException
     */
    public function decode(string $string): array
    {
        $output  = $this->transform(false, $string);
        $decoded = json_decode($output, true);

        if (!is_array($decoded)) {
            throw new LegacyMDTDecodeException(sprintf('cli_weakauras_parser output was not valid JSON: %s', $output));
        }

        return $decoded;
    }

    /**
     * @param  bool                                $encode True to encode, false to decode it.
     * @param  string                              $string The string you want to encode/decode.
     * @throws CliWeakaurasParserNotFoundException
     * @throws LegacyMDTEncodeException
     * @throws LegacyMDTDecodeException
     */
    private function transform(bool $encode, string $string): string
    {
        $fileName = null;

        try {
            $tmpFile = tempnam(sys_get_temp_dir(), 'ksg_mdt_');

            if ($tmpFile === false) {
                throw $this->failure($encode, 'Unable to create a temporary file for cli_weakauras_parser');
            }

            $fileName = $tmpFile;
            file_put_contents($fileName, $string);

            $cmd = sprintf($encode ? self::CLI_PARSER_ENCODE_CMD : self::CLI_PARSER_DECODE_CMD, $fileName);
            $cmd = sprintf('%s %s', self::SUDO, $cmd);

            $process = new Process(explode(' ', $cmd));
            $process->run();

            if (!$process->isSuccessful()) {
                $errorOutput = trim($process->getErrorOutput());

                if (str_contains($errorOutput, 'cli_weakauras_parser: command not found')) {
                    throw new CliWeakaurasParserNotFoundException($errorOutput);
                }

                throw $this->failure($encode, $errorOutput !== '' ? $errorOutput : 'cli_weakauras_parser exited with a non-zero status');
            }

            return $process->getOutput();
        } finally {
            if ($fileName !== null) {
                unlink($fileName);
            }
        }
    }

    private function failure(bool $encode, string $message): LegacyMDTEncodeException|LegacyMDTDecodeException
    {
        return $encode ? new LegacyMDTEncodeException($message) : new LegacyMDTDecodeException($message);
    }
}
