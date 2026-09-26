<?php

namespace App\Service\Spell\Description\Logging;

use App\Logging\Concerns\InteractsWithRollbar;
use App\Logging\StructuredLogging;

class SpellDescriptionImportServiceLogging extends StructuredLogging implements SpellDescriptionImportServiceLoggingInterface
{
    use InteractsWithRollbar;

    public function importDescriptionsStart(string $product, string $build, int $gameVersionId): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    public function importDescriptionsEnd(): void
    {
        $this->end(__METHOD__);
    }

    public function importDescriptionsUnknownBuild(string $product): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    public function importDescriptionsNoDescriptionsFound(string $product, string $build): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    public function persistPvpTalentFlagsTableUnavailable(string $build, string $message): void
    {
        $this->warning(__METHOD__, get_defined_vars());
    }

    public function persistPvpTalentFlagsNoRows(string $build): void
    {
        $this->warning(__METHOD__, get_defined_vars());
    }

    public function persistPvpTalentFlagsDone(string $build, int $pvpTalentSpellCount, int $flaggedCount, int $unflaggedCount): void
    {
        $this->info(__METHOD__, get_defined_vars());
    }

    public function importTranslationsLocaleStart(string $locale): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function importTranslationsLocaleEmpty(string $locale): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }
}
