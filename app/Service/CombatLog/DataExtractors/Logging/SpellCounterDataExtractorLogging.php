<?php

namespace App\Service\CombatLog\DataExtractors\Logging;

use App\Logging\Concerns\InteractsWithRollbar;
use App\Logging\StructuredLogging;

class SpellCounterDataExtractorLogging extends StructuredLogging implements SpellCounterDataExtractorLoggingInterface
{
    use InteractsWithRollbar;

    public function extractDataDetectedSpellCounter(string $signature, int $spellId, string $property, string $playerGuid): void
    {
        $this->info(__METHOD__, get_defined_vars());
    }

    public function extractDataCounterAlreadyQueued(int $spellId, string $property): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function extractDataDebuffExpiredNaturally(int $spellId, int $observedLifetimeMs, int $duration): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function extractDataDebuffNotStrippableByCounter(int $spellId, string $property, string $dispelType): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function extractDataAbandonedCastWasDisturbed(string $casterGuid, int $spellId): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function extractDataAbandonedCastHasNoCounterInWindow(string $casterGuid, int $spellId): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function extractDataAbandonedCastTooOld(string $casterGuid, int $spellId, int $ageMs): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function afterExtractSpellNotFound(int $spellId, string $property): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function afterExtractCounterAlreadyKnown(int $spellId, string $property): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function afterExtractAssignedCounteredSpellToNpc(int $npcId, int $spellId): void
    {
        $this->info(__METHOD__, get_defined_vars());
    }
}
