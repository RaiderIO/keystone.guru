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

    public function afterExtractDungeonEnd(): void;

    public function createSpellAndFetchInfoSpellDataResultIsNull(int $spellId): void;

    public function createSpellAndFetchInfoStart(int $spellId): void;

    public function createSpellAndFetchInfoEnd(): void;
}
