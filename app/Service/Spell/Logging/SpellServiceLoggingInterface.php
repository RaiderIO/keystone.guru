<?php

namespace App\Service\Spell\Logging;

interface SpellServiceLoggingInterface
{
    public function importFromCsvUnableToParseFile(): void;

    public function importFromCsvSpellAlreadySet(int $spellId): void;

    public function importFromCsvInsertResult(bool $insertResult);

    public function getCategoryNameFromClassNameUnableToFindCharacterClass(string $indexClassName): void;

    public function getCategoryNameFromClassNameUnableToFindCategory(string $categoryName): void;

    public function getCooldownGroupNameFromCooldownGroupUnableToFindCategory(string $cooldownGroup): void;
}
