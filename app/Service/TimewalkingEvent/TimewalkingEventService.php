<?php

namespace App\Service\TimewalkingEvent;


use App\Models\AffixGroup\AffixGroup;
use App\Models\Expansion;
use App\Models\Timewalking\TimewalkingEvent;
use App\Service\Season\SeasonService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;

class TimewalkingEventService implements TimewalkingEventServiceInterface
{

    /** @var SeasonService */
    private $seasonService;

    public function __construct()
    {
        $this->seasonService = App::make(SeasonService::class);
    }

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
     * @param Expansion $expansion
     * @param Carbon $date
     * @return AffixGroup|null
     */
    function getAffixGroupAt(Expansion $expansion, Carbon $date): ?AffixGroup
    {
        $timewalkingEvent = $this->getActiveTimewalkingEventAt($date);
        if ($timewalkingEvent === null) {
            return null;
        }

        $result = null;
        if ($timewalkingEvent->expansion_id === $expansion->id) {
            $start = $timewalkingEvent->start();

            // Target date
            $targetTime = Carbon::create($date->year, $date->month, $date->day, $date->hour, null, null, $date->timezone);

            if ($targetTime->gt($start)) {
                $diffInWeeks = $start->diffInWeeks($targetTime);

                if ($diffInWeeks < $timewalkingEvent->start_duration_weeks ||
                    $diffInWeeks % $timewalkingEvent->week_interval === 0) {
                    $result = $this->seasonService->getCurrentSeason($expansion)->affixgroups->get($diffInWeeks % $timewalkingEvent->week_interval);
                }
            }
        } else {
            logger()->error('Overlapping timewalking events found?', [
                $timewalkingEvent, $expansion,
            ]);
        }

        return $result;
    }

}
