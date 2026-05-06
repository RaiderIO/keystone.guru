<?php

namespace App\Service\AffixGroup\Logging;

use App\Logging\RollbarStructuredLogging;
use Exception;

class AffixGroupEaseTierServiceLogging extends RollbarStructuredLogging implements AffixGroupEaseTierServiceLoggingInterface
{
    public function parseTierListUnknownAffixGroup(?string $affixGroupString): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    public function parseTierListInvalidLastUpdated(Exception $exception, string $lastUpdated): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    public function parseTierListParseTierStart(string $affixGroupString, string $tier, int $count): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    public function parseTierListUnknownDungeon(string $dungeonName): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    public function parseTierListSavedDungeonTier(string $dungeonName, string $tier): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function parseTierListParseTierEnd(): void
    {
        $this->end(__METHOD__);
    }

    public function parseTierListSave(bool $result): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function parseTierListDataNotUpdatedYet(): void
    {
        $this->info(__METHOD__);
    }

    public function getAffixGroupByStringUnknownAffixes(string $unknownAffixes): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }
}
