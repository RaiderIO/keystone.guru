<?php

namespace App\Service\Traits\Dtos;

/**
 * The outcome of {@see \App\Service\Traits\Curl::curlDownloadToFile()}: whether the file landed on disk, and
 * enough of the transport outcome to tell why it did not.
 */
readonly class CurlDownloadResult
{
    /**
     * 4xx statuses a later attempt can still succeed on: 403 is what S3 answers an expired or not (yet) valid
     * presigned URL with, and 408/429 are the server asking to be asked again.
     */
    private const array RETRYABLE_CLIENT_ERROR_HTTP_CODES = [403, 408, 429];

    public function __construct(
        public bool   $succeeded,
        public int    $httpCode,
        public int    $errorNumber,
        public string $errorMessage,
        public float  $durationSeconds,
    ) {
    }

    /**
     * A failure no retry can fix: the object's body cannot be decoded (a truncated or invalid gzip body stored
     * with `Content-Encoding: gzip` fails the same way on every URL), or the server answered with a 4xx that is
     * not about the request's signature or timing.
     */
    public function isPermanent(): bool
    {
        if ($this->succeeded) {
            return false;
        }

        if ($this->errorNumber === CURLE_BAD_CONTENT_ENCODING) {
            return true;
        }

        return $this->errorNumber === CURLE_OK
            && $this->httpCode >= 400 && $this->httpCode < 500
            && !in_array($this->httpCode, self::RETRYABLE_CLIENT_ERROR_HTTP_CODES, true);
    }

    /**
     * A failure a freshly signed URL may fix: the presigned URL expired or was denied, or the transfer timed out
     * - which can run a later segment's URL past its expiry before it was even requested.
     */
    public function isExpiredOrDenied(): bool
    {
        if ($this->succeeded) {
            return false;
        }

        return ($this->errorNumber === CURLE_OK && $this->httpCode === 403)
            || $this->errorNumber === CURLE_OPERATION_TIMEDOUT;
    }
}
