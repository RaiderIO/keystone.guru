<?php

namespace App\Service\CombatLog\DataExtractors\Logging;

use App\Logging\StructuredLogging;

class SpellDataExtractorLogging extends StructuredLogging implements SpellDataExtractorLoggingInterface
{
    public function isSummonedNpcNpcWasSummoned(int $npcId, string $npcName): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function assignDungeonToSpellAssignedDungeonToSpell(int $spellId, int $dungeonId): void
    {
        $this->info(__METHOD__, get_defined_vars());
    }

    public function extractDataAssignedSpellToNpc(int $npcId, int $spellId, string $rawEvent): void
    {
        $this->info(__METHOD__, get_defined_vars());
    }

    public function extractDataSpellNotAssignedToNpc(bool $destIsNpc, string $auraType): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }


    public function afterExtractDungeonStart(string $dungeonName): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    public function createMissingSpellCreatedSpell(string $name, int $spellId): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function afterExtractDungeonEnd(): void
    {
        $this->end(__METHOD__);
    }

    public function createSpellAndFetchInfoSpellDataResultIsNull(int $spellId): void
    {
        $this->warning(__METHOD__, get_defined_vars());
    }

    public function createSpellAndFetchInfoStart(int $spellId): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    public function createSpellAndFetchInfoEnd(): void
    {
        $this->end(__METHOD__);
    }


}
