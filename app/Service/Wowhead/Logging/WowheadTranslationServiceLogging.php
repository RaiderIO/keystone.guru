<?php

namespace App\Service\Wowhead\Logging;

use App\Logging\StructuredLogging;

class WowheadTranslationServiceLogging extends StructuredLogging implements WowheadTranslationServiceLoggingInterface
{
    public function getDungeonNamesStart(): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    public function getDungeonNamesLocaleStart(string $locale): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    public function getDungeonNamesWowheadUrl(string $url): void
    {
        $this->info(__METHOD__, get_defined_vars());
    }

    public function getDungeonNamesSetDungeonName(string $dungeon, string $localizedName): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function getDungeonNamesLocaleEnd(): void
    {
        $this->end(__METHOD__, get_defined_vars());
    }

    public function getDungeonNamesEnd(): void
    {
        $this->end(__METHOD__, get_defined_vars());
    }
}
