<?php

namespace App\Service\MDT;

use App\Logic\MDT\Exception\CliWeakaurasParserNotFoundException;
use App\Logic\MDT\IO\MDTStringFormat;
use Lua;
use Throwable;

abstract class MDTBaseService
{
    /**
     * Gets a Lua instance and load all the required files in it.
     */
    protected function getLua(): Lua
    {
        $lua = new Lua();

        // Load libraries (yeah can do this with ->library function as well)
        $lua->eval(file_get_contents(base_path('app/Logic/MDT/Lua/LibStub.lua')));
        $lua->eval(file_get_contents(base_path('app/Logic/MDT/Lua/LibCompress.lua')));
        $lua->eval(file_get_contents(base_path('app/Logic/MDT/Lua/LibDeflate.lua')));
        $lua->eval(file_get_contents(base_path('app/Logic/MDT/Lua/AceSerializer.lua')));
        $lua->eval(file_get_contents(base_path('app/Logic/MDT/Lua/MDTTransmission.lua')));

        return $lua;
    }

    /**
     * @param  array<string, mixed>                $contents
     * @param  MDTStringFormat                     $format   Which MDT export-string format to encode into. Defaults to
     *                                                       the legacy format until MDT drops legacy import support with
     *                                                       WoW 12.2.
     * @throws CliWeakaurasParserNotFoundException
     * @throws Throwable
     */
    protected function encode(array $contents, MDTStringFormat $format = MDTStringFormat::Legacy): string
    {
        return $format->codec()->encode($contents);
    }

    /**
     * @return array<string, mixed>|null           Null if the string could not be decoded
     * @throws CliWeakaurasParserNotFoundException
     */
    protected function decode(string $string): ?array
    {
        $format = MDTStringFormat::detect($string);

        try {
            return $format->codec()->decode($string);
        } catch (CliWeakaurasParserNotFoundException $cliWeakaurasParserNotFoundException) {
            throw $cliWeakaurasParserNotFoundException;
        } catch (Throwable $throwable) {
            // Truncated: the input is unvalidated user input of arbitrary size.
            $context = ['string' => substr($string, 0, 2048)];

            if ($format === MDTStringFormat::MDT2) {
                // detect() only picks MDT2 for a strict `!~MDT2~` prefix match, so a decode
                // failure here means our own CBOR codec choked on a string that genuinely claimed
                // to be an MDT export - always worth reporting.
                logger()->error($throwable->getMessage(), $context);
            } else {
                // detect() defaults to Legacy for everything else, including garbage that doesn't
                // even look like a legacy MDT export string (LegacyMDTCodec shells out to
                // cli_weakauras_parser, whose stderr becomes the exception message) - most of these
                // are just malformed user input, not application bugs (#3906).
                logger()->warning($throwable->getMessage(), $context);
            }

            return null;
        }
    }
}
