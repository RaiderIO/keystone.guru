<?php

namespace App\Service\MDT;

use App\Logic\MDT\Exception\CliWeakaurasParserNotFoundException;
use App\Logic\MDT\IO\MDTStringFormat;
use App\Service\MDT\Logging\MDTBaseServiceLoggingInterface;
use Lua;
use Throwable;

abstract class MDTBaseService
{
    public function __construct(private readonly MDTBaseServiceLoggingInterface $log)
    {
    }

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
            $truncatedString = substr($string, 0, 2048);

            // Whether the string plausibly claims to be an MDT export string at all decides whether this
            // is worth alerting on - see the two logging methods for the reasoning behind each level.
            if (MDTStringFormat::isValid($string)) {
                $this->log->decodeFailed($truncatedString, $throwable::class, $throwable->getMessage());
            } else {
                $this->log->decodeInvalidStringFailed($truncatedString, $throwable::class, $throwable->getMessage());
            }

            return null;
        }
    }
}
