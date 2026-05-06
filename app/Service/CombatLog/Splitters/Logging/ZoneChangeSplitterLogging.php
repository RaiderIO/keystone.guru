<?php

namespace App\Service\CombatLog\Splitters\Logging;

class ZoneChangeSplitterLogging extends CombatLogSplitterLogging implements ZoneChangeSplitterLoggingInterface
{
    public function parseCombatLogEventTimestampNotSet(): void
    {
        $this->info(__METHOD__);
    }

    public function parseCombatLogEventZoneChangeEvent(): void
    {
        $this->debug(__METHOD__);
    }

    public function parseCombatLogEventCombatLogVersionEvent(): void
    {
        $this->debug(__METHOD__);
    }

    public function resetCurrentZone(): void
    {
        $this->debug(__METHOD__);
    }

    public function reset(): void
    {
        $this->debug(__METHOD__);
    }
}
