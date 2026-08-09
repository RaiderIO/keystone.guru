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
        try {
            return MDTStringFormat::detect($string)->codec()->decode($string);
        } catch (CliWeakaurasParserNotFoundException $cliWeakaurasParserNotFoundException) {
            throw $cliWeakaurasParserNotFoundException;
        } catch (Throwable $throwable) {
            // Truncated: the input is unvalidated user input of arbitrary size.
            $context = ['string' => substr($string, 0, 2048)];

            if (MDTStringFormat::isValid($string)) {
                // The string at least plausibly claims to be an MDT export (one of the codecs'
                // appliesTo() matched), so a decode failure here is worth reporting - it's either a
                // genuine bug in our own codec, or (for the Legacy format) a real but corrupted/
                // truncated export that a user has a right to expect us to import.
                logger()->error($throwable->getMessage(), $context);
            } else {
                // detect() unconditionally falls back to Legacy for anything that isn't MDT2, even
                // garbage that doesn't look like a legacy MDT export at all - LegacyMDTCodec shells
                // out to cli_weakauras_parser, whose stderr becomes the exception message, so this
                // covers the common case of a user pasting non-MDT-string input (#3906).
                logger()->warning($throwable->getMessage(), $context);
            }

            return null;
        }
    }
}
