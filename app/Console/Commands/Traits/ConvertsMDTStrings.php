<?php

namespace App\Console\Commands\Traits;

use App\Logic\MDT\IO\MDTStringFormat;
use Illuminate\Console\Command;
use Throwable;

/**
 * Dispatches MDT export-string encoding/decoding to the correct format implementation (see
 * MDTStringFormat / MDTStringCodecInterface) and wires up the CLI-specific error reporting -
 * console output plus, for decode failures, structured logging - both artisan commands need.
 *
 * @mixin Command
 */
trait ConvertsMDTStrings
{
    /**
     * @param  array<int|string, mixed> $contents
     * @param  MDTStringFormat          $format   Which MDT export-string format to encode into.
     * @return string|null              Null if encoding failed - the failure is already reported
     *                                  via $this->error().
     */
    protected function encode(array $contents, MDTStringFormat $format = MDTStringFormat::Legacy): ?string
    {
        try {
            return $format->codec()->encode($contents);
        } catch (Throwable $throwable) {
            $this->error($throwable->getMessage());

            return null;
        }
    }

    /**
     * @return array<int|string, mixed>|null Null if the string could not be decoded
     */
    protected function decode(string $string): ?array
    {
        $format = MDTStringFormat::detect($string);

        try {
            return $format->codec()->decode($string);
        } catch (Throwable $throwable) {
            $this->error($throwable->getMessage());

            // detect() matched a format, so this string genuinely claimed to be an MDT export -
            // always worth reporting. Truncated: decode() is fed unvalidated user input of
            // arbitrary size.
            logger()->error($throwable->getMessage(), [
                'string' => substr($string, 0, 2048),
            ]);

            return null;
        }
    }

    /**
     * Checks whether $string is genuinely encoded in either MDT export-string format this app
     * understands. Unlike encode()/decode() above, this is a plain static check with no
     * console/logging side effects, so - unlike them - it is safe to call from any class, not just
     * a Command. See MDTStringFormat::isValid() for what "genuinely" means per format.
     */
    public static function isValidMdtString(string $string): bool
    {
        return MDTStringFormat::isValid($string);
    }
}
