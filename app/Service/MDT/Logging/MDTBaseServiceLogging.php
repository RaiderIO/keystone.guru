<?php

namespace App\Service\MDT\Logging;

use App\Logging\StructuredLogging;

class MDTBaseServiceLogging extends StructuredLogging implements MDTBaseServiceLoggingInterface
{
    /**
     * The string at least plausibly claims to be an MDT export (one of the codecs' appliesTo() matched),
     * so a decode failure here is worth reporting - it's either a genuine bug in our own codec, or (for
     * the Legacy format) a real but corrupted/truncated export that a user has a right to expect us to
     * import.
     *
     * Logged at error, which reaches Discord and Sentry.
     *
     * The $exceptionClass and $message parameter names are load-bearing: FingerprintsStructuredErrorsHandler
     * keys on those literal context keys to fingerprint and tag the resulting Sentry issue.
     */
    public function decodeFailed(string $string, string $exceptionClass, string $message): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    /**
     * MDTStringFormat::detect() unconditionally falls back to Legacy for anything that isn't MDT2, even
     * garbage that doesn't look like a legacy MDT export at all - LegacyMDTCodec shells out to
     * cli_weakauras_parser, whose stderr becomes the exception message, so this covers the common case of
     * a user pasting non-MDT-string input (#3906).
     *
     * Logged at warning: visible in the logs, but no Discord/Sentry alert - the controller already turns
     * this into a clean 400 for the user.
     */
    public function decodeInvalidStringFailed(string $string, string $exceptionClass, string $message): void
    {
        $this->warning(__METHOD__, get_defined_vars());
    }
}
