<?php

namespace App\Service\MDT;

use App\Logic\MDT\Exception\CliWeakaurasParserNotFoundException;
use App\Logic\MDT\Exception\MDT2DecodeException;
use App\Logic\MDT\Exception\MDT2EncodeException;
use App\Logic\MDT\IO\MDT2Codec;
use Illuminate\Support\Facades\Artisan;
use Lua;

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
     * @param  array<string, mixed> $contents
     * @param  bool                 $useMDT2Format True to encode into the MDT 6.2+ `!~MDT2~` format instead of the
     *                                             legacy `!` format. Kept off by default until MDT drops legacy
     *                                             import support with WoW 12.2.
     * @throws MDT2EncodeException
     */
    protected function encode(array $contents, bool $useMDT2Format = false): string
    {
        if ($useMDT2Format) {
            return MDT2Codec::encode($contents);
        }

        Artisan::call('mdt:encode', ['string' => json_encode($contents)]);

        $output = trim(Artisan::output());

        if (str_contains($output, 'cli_weakauras_parser: command not found')) {
            throw new CliWeakaurasParserNotFoundException($output);
        }

        return $output;
    }

    /**
     * @return array<string, mixed>|null Null if the string could not be decoded
     */
    protected function decode(string $string): ?array
    {
        // MDT 6.2+ strings are decoded natively in PHP - cli_weakauras_parser only handles the legacy format
        if (MDT2Codec::appliesTo($string)) {
            try {
                return MDT2Codec::decode($string);
            } catch (MDT2DecodeException $mdt2DecodeException) {
                // The prefix matched, so this string genuinely claimed to be an MDT export - always worth
                // reporting, mirroring what ConvertsMDTStrings::transform() does for plausible legacy strings.
                // Truncated: the input is unvalidated user input of arbitrary size
                logger()->error($mdt2DecodeException->getMessage(), [
                    'string' => substr($string, 0, 2048),
                ]);

                return null;
            }
        }

        Artisan::call('mdt:decode', ['string' => $string]);

        $output = trim(Artisan::output());

        if (str_contains($output, 'cli_weakauras_parser: command not found')) {
            throw new CliWeakaurasParserNotFoundException($output);
        }

        return json_decode($output, true);
    }
}
