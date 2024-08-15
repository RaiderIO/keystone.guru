<?php

namespace App\Service\CombatLog\DataExtractors\Logging;

interface CreateMissingNpcDataExtractorLoggingInterface
{

    public function extractDataNpcNotFound(int $npcId): void;

    public function extractDataNpcNameNotFound(?string $sourceGuid, ?string $destGuid): void;

    public function extractDataNpcWasSummoned(int $npcId, string $name): void;

    public function extractDataNpcWasAPet(int $npcId, string $name): void;

    public function extractDataCreatedNpc(int $npcId, string $name, int $baseHealth, string $rawEvent): void;

    public function extractDataNpcNotCreated(int $npcId, string $name): void;
}
