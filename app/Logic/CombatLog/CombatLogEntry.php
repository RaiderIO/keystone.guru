<?php

namespace App\Logic\CombatLog;


use Carbon\Carbon;

class CombatLogEntry
{
    private Carbon $timestamp;

    private string $event;

    /**
     * @param string $timestamp
     * @param string $event
     */
    public function __construct(string $timestamp, string $event)
    {
        $this->timestamp = \Carbon\Carbon::createFromFormat('m/d H:i:s.v', $timestamp);


    }

    /**
     * @return Carbon
     */
    public function getTimestamp(): Carbon
    {
        return $this->timestamp;
    }
}
