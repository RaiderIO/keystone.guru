<?php

namespace App\Logic\SimulationCraft;

/**
 * Adds that join a pull in waves. SimulationCraft summons them as pets of the main target that live for the duration
 * unless killed sooner; `force_stop=1` dismisses whatever is left of them once the pull's last enemy dies, so they
 * never outlive their pull.
 */
class RaidEventPullAdds extends RaidEventPullChildEvent
{
    public function __construct(
        int                     $pullIndex,
        RaidEventPullTiming     $timing,
        private readonly string $name,
        private readonly int    $count,
        private readonly int    $health,
        private readonly int    $durationMs,
    ) {
        parent::__construct($pullIndex, $timing);
    }

    protected function getType(): string
    {
        return 'adds';
    }

    protected function getLeadingOptions(): array
    {
        return [
            sprintf('name=%s', $this->name),
            sprintf('count=%d', $this->count),
            sprintf('health=%d', $this->health),
        ];
    }

    protected function getEventOptions(): array
    {
        return [
            sprintf('duration=%s', RaidEventPullTiming::formatSeconds($this->durationMs)),
            'force_stop=1',
        ];
    }
}
