<?php

namespace App\Service\Traits;

trait CombatLogSegmentFile
{
    /**
     * Derive the file extension from the (presigned) download URL. The extraction service relies on the
     * `.zip` extension to know it must unzip the archive before parsing; without it the raw archive bytes
     * are fed to the parser. Other downloads (e.g. the `.txt.gz`-named Raider.IO segments, whose bodies
     * arrive already decompressed via the request's content encoding) are plain text and saved as `.txt`.
     */
    public function resolveSegmentExtension(string $downloadUrl): string
    {
        $path = (string)parse_url($downloadUrl, PHP_URL_PATH);

        return str_ends_with($path, '.zip') ? 'zip' : 'txt';
    }

    /**
     * A second line of defence against a bad download reaching the parser: `curlSaveToFile()` already rejects a
     * non-2xx response, but a proxy or CDN in front of S3 may hand back an error document with a 200, and an
     * empty body is a successful response by every measure curl reports.
     *
     * Neither a combat log (which starts with a timestamp) nor a zip archive (`PK`) can start with `<`, so an
     * opening angle bracket means an XML or HTML document rather than the segment we asked for. A zero-byte
     * segment carries nothing to extract either way, and is far more likely a broken download than a real
     * (empty) part of a run - failing it retries rather than silently reporting a successful ingest (see #3789).
     */
    public function isPlausibleSegment(string $filePath): bool
    {
        if (!is_file($filePath) || filesize($filePath) === 0) {
            return false;
        }

        return file_get_contents($filePath, length: 1) !== '<';
    }
}
