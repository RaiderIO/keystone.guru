<?php

namespace App\Service\Wowhead\Logging;

use App\Logging\StructuredLogging;

class WowheadServiceLogging extends StructuredLogging implements WowheadServiceLoggingInterface
{
    public function getNpcHealthHtmlParsingException(\Throwable $ex): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    public function downloadMissingSpellIconsStart(): void
    {
        $this->start(__METHOD__);
    }

    public function downloadMissingSpellIconsEnd(): void
    {
        $this->end(__METHOD__);
    }

    public function getSpellDataSpellDoesNotExist(string $gameVersion, int $spellId): void
    {
        $this->warning(__METHOD__, get_defined_vars());
    }

    public function downloadSpellIconDownloadResult(string $targetFilePath, bool $result): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function getSpellDataIconNameNotFound(string $line, string $jsonString): void
    {
        $this->info(__METHOD__, get_defined_vars());
    }

    public function getSpellDataIconNameSpellIdDoesNotMatch(string $line, array $json, int $spellId): void
    {
        $this->warning(__METHOD__, get_defined_vars());
    }

    public function getSpellDataSpellSchoolNotFound(string $schoolsStr, string $school): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    public function getSpellDataSpellDispelTypeNotFound(string $dispelType): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    public function getSpellDataDataNotSet(
        bool $mechanicSet,
        bool $schoolSet,
        bool $dispelTypeSet,
        bool $castTimeSet,
        bool $durationSet,
    ): void {
        $this->warning(__METHOD__, get_defined_vars());
    }
}
