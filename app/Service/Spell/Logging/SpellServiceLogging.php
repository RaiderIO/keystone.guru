<?php

namespace App\Service\Spell\Logging;

use App\Logging\StructuredLogging;

class SpellServiceLogging extends StructuredLogging implements SpellServiceLoggingInterface
{
    public function importFromCsvUnableToParseFile(): void
    {
        $this->error(__METHOD__);
    }

    public function importFromCsvUnableToFindCharacterClass(string $indexClassName): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    public function importFromCsvUnableToFindCategory(string $categoryName): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

}
