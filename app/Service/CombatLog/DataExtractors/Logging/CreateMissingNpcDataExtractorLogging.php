<?php

namespace App\Service\CombatLog\DataExtractors\Logging;

use App\Logging\RollbarStructuredLogging;

class CreateMissingNpcDataExtractorLogging extends RollbarStructuredLogging implements CreateMissingNpcDataExtractorLoggingInterface
{
    public function extractDataNpcNotFound(int $npcId): void
    {
        $this->warning(__METHOD__, get_defined_vars());
    }

    public function extractDataNpcNameNotFound(?string $sourceGuid, ?string $destGuid): void
    {
        $this->warning(__METHOD__, get_defined_vars());
    }

    public function extractDataNpcWasSummoned(int $npcId, string $name): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function extractDataNpcWasAPet(int $npcId, string $name): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function extractDataCreatedNpc(int $npcId, string $name, int $baseHealth, string $rawEvent): void
    {
        $this->info(__METHOD__, get_defined_vars());
    }

    public function extractDataNpcNotCreated(int $npcId, string $name, int $baseHealth): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }
}
