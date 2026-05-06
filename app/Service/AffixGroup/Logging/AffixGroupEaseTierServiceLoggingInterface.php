<?php

namespace App\Service\AffixGroup\Logging;

use Exception;

interface AffixGroupEaseTierServiceLoggingInterface
{
    public function parseTierListUnknownAffixGroup(?string $affixGroupString): void;

    public function parseTierListInvalidLastUpdated(Exception $exception, string $lastUpdated): void;

    public function parseTierListParseTierStart(string $affixGroupString, string $tier, int $count): void;

    public function parseTierListUnknownDungeon(string $dungeonName): void;

    public function parseTierListSavedDungeonTier(string $dungeonName, string $tier): void;

    public function parseTierListParseTierEnd(): void;

    public function parseTierListSave(bool $result): void;

    public function parseTierListDataNotUpdatedYet(): void;

    public function getAffixGroupByStringUnknownAffixes(string $unknownAffixes): void;
}
