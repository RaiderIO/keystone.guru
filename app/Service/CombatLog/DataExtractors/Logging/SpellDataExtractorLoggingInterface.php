<?php

namespace App\Service\CombatLog\DataExtractors\Logging;

interface SpellDataExtractorLoggingInterface
{
    public function isSummonedNpcNpcWasSummoned(int $npcId, string $npcName): void;

    public function assignDungeonToSpellAssignedDungeonToSpell(int $spellId, int $dungeonId): void;

    public function extractDataAssignedSpellToNpc(int $npcId, int $spellId, string $rawEvent): void;

    public function extractDataSpellNpcNull(int $npcId): void;

    public function afterExtractDungeonStart(string $dungeonName): void;

    public function createMissingSpellCreatedSpell(string $name, int $spellId): void;

    public function ensureSpellExistsRepairedSchoolsMask(int $spellId, int $schoolsMask): void;

    /** Another worker won the race to create this spell id between our own lookup and insert (#4151). */
    public function createSpellLostRaceFoundExistingSpell(int $spellId): void;

    /** The insert collided on this spell id, but a re-fetch could not find the row that must have caused it. */
    public function createSpellLostRaceSpellVanished(int $spellId): void;

    public function afterExtractDungeonEnd(): void;
}
