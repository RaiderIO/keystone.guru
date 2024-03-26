<?php

namespace App\Service\Spell\Logging;

interface SpellServiceLoggingInterface
{
    public function importFromCsvUnableToParseFile(): void;

    public function importFromCsvUnableToFindCharacterClass(string $indexClassName): void;

    public function importFromCsvUnableToFindCategory(string $categoryName): void ;
}
