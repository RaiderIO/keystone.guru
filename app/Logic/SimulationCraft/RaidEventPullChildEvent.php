<?php

namespace App\Logic\SimulationCraft;

/**
 * A raid event that belongs to a pull (`pull=<n>`): SimulationCraft starts its timer when the pull spawns and stops
 * scheduling it once every enemy of the pull has died.
 */
abstract class RaidEventPullChildEvent implements RaidEventOutputInterface
{
    public function __construct(
        protected readonly int                 $pullIndex,
        protected readonly RaidEventPullTiming $timing,
    ) {
    }

    /**
     * The raid event type, e.g. adds.
     */
    abstract protected function getType(): string;

    /**
     * The options that follow the timing.
     *
     * @return list<string>
     */
    abstract protected function getEventOptions(): array;

    /**
     * Options that go before the timing, e.g. a name.
     *
     * @return list<string>
     */
    protected function getLeadingOptions(): array
    {
        return [];
    }

    public function toString(): string
    {
        return sprintf(
            'raid_events+=/%s,pull=%02d,%s',
            $this->getType(),
            $this->pullIndex,
            implode(',', array_merge($this->getLeadingOptions(), $this->timing->getOptions(), $this->getEventOptions())),
        );
    }
}
