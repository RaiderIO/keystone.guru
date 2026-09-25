<?php

namespace App\Service\CombatLog\DataExtractors\Logging;

interface NpcCharacteristicDataExtractorLoggingInterface
{
    public function constructSpellEvidenceNotConclusive(int $spellId, string $characteristicKey): void;

    public function extractDataAssignedCharacteristicToNpc(int $npcId, string $characteristicKey, string $rawEvent): void;

    public function extractDataNpcNotFound(int $npcId): void;

    public function extractDataCharacteristicAlreadyAssigned(int $npcId, string $characteristicKey): void;
}
