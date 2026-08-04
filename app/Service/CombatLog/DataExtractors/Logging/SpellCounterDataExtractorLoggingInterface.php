<?php

namespace App\Service\CombatLog\DataExtractors\Logging;

interface SpellCounterDataExtractorLoggingInterface
{
    public function extractDataDetectedSpellCounter(string $signature, int $spellId, string $property, string $playerGuid): void;

    public function extractDataCounterAlreadyQueued(int $spellId, string $property): void;

    public function extractDataDebuffExpiredNaturally(int $spellId, int $observedLifetimeMs, int $duration): void;

    public function extractDataAbandonedCastWasDisturbed(string $casterGuid, int $spellId): void;

    public function extractDataAbandonedCastHasNoCounterInWindow(string $casterGuid, int $spellId): void;

    public function extractDataAbandonedCastTooOld(string $casterGuid, int $spellId, int $ageMs): void;

    public function afterExtractSpellNotFound(int $spellId, string $property): void;

    public function afterExtractCounterAlreadyKnown(int $spellId, string $property): void;

    public function afterExtractAssignedCounteredSpellToNpc(int $npcId, int $spellId): void;
}
