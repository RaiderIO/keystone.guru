<?php

namespace App\Service\TimewalkingEvent;


use App\Models\Timewalking\TimewalkingEvent;
use App\Models\Timewalking\TimewalkingEventAffixGroup;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class TimewalkingEventService implements TimewalkingEventServiceInterface
{
    /**
     * @param Carbon $date
     * @return TimewalkingEvent|null
     */
    function getActiveTimewalkingEventAt(Carbon $date): ?TimewalkingEvent
    {
        $result = null;

        /** @var Collection|TimewalkingEvent[] $timewalkingEvents */
        $timewalkingEvents = TimewalkingEvent::all();

        foreach ($timewalkingEvents as $timewalkingEvent) {
            $start = $timewalkingEvent->start();

            // Target date
            $targetTime = Carbon::create($date->year, $date->month, $date->day, $date->hour, null, null, $date->timezone);

            if ($targetTime->gt($start)) {
                $diffInWeeks = $start->diffInWeeks($targetTime);

                if ($diffInWeeks < $timewalkingEvent->start_duration_weeks ||
                    $diffInWeeks % $timewalkingEvent->week_interval === 0) {
                    $result = $timewalkingEvent;
                    break;
                }
            }
        }

        return $result;
    }

    /**
     * @param Carbon $date
     * @return TimewalkingEventAffixGroup|null
     */
    function getTimewalkingEventAffixGroupAt(Carbon $date): ?TimewalkingEventAffixGroup
    {
        $result = null;

        $timewalkingEvent = $this->getActiveTimewalkingEventAt($date);

        if ($timewalkingEvent !== null) {
            $start = $timewalkingEvent->start();

            // Target date
            $targetTime = Carbon::create($date->year, $date->month, $date->day, $date->hour, null, null, $date->timezone);

            if ($targetTime->gt($start)) {
                $diffInWeeks = $start->diffInWeeks($targetTime);

                if ($diffInWeeks < $timewalkingEvent->start_duration_weeks ||
                    $diffInWeeks % $timewalkingEvent->week_interval === 0) {
                    $result = $timewalkingEvent->timewalkingeventaffixgroups->get($diffInWeeks % $timewalkingEvent->week_interval);
                }
            }
        }

        return $result;
    }

}
