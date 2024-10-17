<?php

namespace App\Service\Wowhead\Logging;

interface WowheadServiceLoggingInterface
{

    public function getNpcHealthHtmlParsingException(\Throwable $ex): void;

    public function downloadMissingSpellIconsStart(): void;

    public function downloadMissingSpellIconsEnd(): void;

    public function downloadSpellIconDownloadResult(string $targetFilePath, bool $result): void;

    public function getSpellDataSpellDoesNotExist(string $gameVersion, int $spellId): void;

    public function getSpellDataIconNameNotFound(string $line, string $jsonString): void;

    public function getSpellDataIconNameSpellIdDoesNotMatch(string $line, array $json, int $spellId): void;

    public function getSpellDataSpellSchoolNotFound(string $schoolsStr, string $school): void;

    public function getSpellDataSpellDispelTypeNotFound(string $dispelType): void;

    public function getSpellDataDataNotSet(bool $mechanicSet, bool $schoolSet, bool $dispelTypeSet, bool $castTimeSet, bool $durationSet): void;
}
