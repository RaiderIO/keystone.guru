<?php

namespace App\Service\CombatLog\DataExtractors\Logging;

interface ImmunityBypassDataExtractorLoggingInterface
{
    public function extractDataOpenedImmunityWindow(string $playerGuid, int $buffSpellId, string $property): void;

    public function extractDataDetectedImmunityBypass(string $kind, int $spellId, string $property, string $playerGuid, int $windowOffsetMs): void;

    public function extractDataCandidateRejectedAtWindowEnd(int $spellId, string $property, int $offsetToRemovalMs): void;

    public function extractDataCandidateResolvedBeforeWindow(int $spellId, string $property, int $windowOffsetMs): void;

    public function extractDataBypassAlreadyQueued(int $spellId, string $property): void;

    public function afterExtractSpellNotFound(int $spellId, string $property): void;

    public function afterExtractBypassAlreadyKnown(int $spellId, string $property): void;

    public function afterExtractAssignedBypassingSpellToNpc(int $npcId, int $spellId): void;
}
