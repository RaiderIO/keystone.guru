<?php

namespace App\Service\Spell\Logging;

use App\Logging\StructuredLogging;

class SpellServiceLogging extends StructuredLogging implements SpellServiceLoggingInterface
{
    public function importFromCsvUnableToParseFile(): void
    {
        $this->error(__METHOD__);
    }

    public function importFromCsvSpellIdEmpty(): void
    {
        $this->warning(__METHOD__);
    }

    public function importFromCsvSpellAlreadySet(int $spellId): void
    {
        $this->warning(__METHOD__, get_defined_vars());
    }

    public function importFromCsvInsertNewSpell(int $spellId): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function importFromCsvInsertResult(int $updated, int $inserted): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function getCategoryNameFromClassNameUnableToFindCharacterClass(string $indexClassName): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    public function getCategoryNameFromClassNameUnableToFindCategory(string $categoryName): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    public function getCooldownGroupNameFromCooldownGroupUnableToFindCategory(string $cooldownGroup): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }
}
