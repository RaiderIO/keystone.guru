<?php

namespace App\Service\Spell\Logging;

interface SpellServiceLoggingInterface
{
    public function importFromCsvUnableToParseFile(): void;

    public function importFromCsvSpellIdEmpty(): void;

    public function importFromCsvSpellAlreadySet(int $spellId): void;

    public function importFromCsvInsertNewSpell(int $spellId): void;

    public function importFromCsvInsertResult(int $updated, int $inserted): void;

    public function getCategoryNameFromClassNameUnableToFindCharacterClass(string $indexClassName): void;

    public function getCategoryNameFromClassNameUnableToFindCategory(string $categoryName): void;

    public function getCooldownGroupNameFromCooldownGroupUnableToFindCategory(string $cooldownGroup): void;
}
