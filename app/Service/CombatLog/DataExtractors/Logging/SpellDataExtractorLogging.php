<?php

namespace App\Service\CombatLog\DataExtractors\Logging;

use App\Logging\StructuredLogging;

class SpellDataExtractorLogging extends StructuredLogging implements SpellDataExtractorLoggingInterface
{
    public function extractDataAssignedDungeonToSpell(int $spellId, int $dungeonId): void
    {
        $this->info(__METHOD__, get_defined_vars());
    }

    public function afterExtractDungeonStart(string $dungeonName): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    public function afterExtractCreatedSpell(string $name, int $spellId): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function afterExtractDungeonEnd(): void
    {
        $this->end(__METHOD__);
    }
}
