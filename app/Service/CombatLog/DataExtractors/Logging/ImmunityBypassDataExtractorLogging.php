<?php

namespace App\Service\CombatLog\DataExtractors\Logging;

use App\Logging\Concerns\InteractsWithRollbar;
use App\Logging\StructuredLogging;

class ImmunityBypassDataExtractorLogging extends StructuredLogging implements ImmunityBypassDataExtractorLoggingInterface
{
    use InteractsWithRollbar;

    public function extractDataOpenedImmunityWindow(string $playerGuid, int $buffSpellId, string $property): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function extractDataDetectedImmunityBypass(string $kind, int $spellId, string $property, string $playerGuid, int $windowOffsetMs): void
    {
        $this->info(__METHOD__, get_defined_vars());
    }

    public function extractDataCandidateRejectedAtWindowEnd(int $spellId, string $property, int $offsetToRemovalMs): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function extractDataCandidateResolvedBeforeWindow(int $spellId, string $property, int $windowOffsetMs): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function extractDataBypassAlreadyQueued(int $spellId, string $property): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function afterExtractSpellNotFound(int $spellId, string $property): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function afterExtractBypassAlreadyKnown(int $spellId, string $property): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function afterExtractAssignedBypassingSpellToNpc(int $npcId, int $spellId): void
    {
        $this->info(__METHOD__, get_defined_vars());
    }
}
