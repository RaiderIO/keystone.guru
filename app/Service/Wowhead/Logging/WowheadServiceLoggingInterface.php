<?php

namespace App\Service\Wowhead\Logging;

interface WowheadServiceLoggingInterface
{

    public function downloadMissingSpellIconsStart(): void;

    public function downloadMissingSpellIconsFileExists(string $targetFile): void;

    public function downloadMissingSpellIconsEnd(): void;

    public function downloadSpellIconDownloadResult(string $targetFilePath, bool $result): void;
}
