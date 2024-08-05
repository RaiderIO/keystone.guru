<?php

namespace App\Service\CombatLog\DataExtractors\Logging;

interface SpellDataExtractorLoggingInterface
{
    public function extractDataAssignedDungeonToSpell(int $spellId, int $dungeonId): void;

    public function afterExtractDungeonStart(string $dungeonName): void;

    public function afterExtractCreatedSpell(string $name, int $spellId): void;

    public function afterExtractDungeonEnd(): void;
}
