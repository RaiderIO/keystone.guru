<?php

namespace App\Service\TimewalkingEvent;


use App\Models\AffixGroup\AffixGroup;
use App\Models\Expansion;
use App\Models\Timewalking\TimewalkingEvent;
use App\Service\Season\SeasonService;
use App\Service\Season\SeasonServiceInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class TimewalkingEventService implements TimewalkingEventServiceInterface
{
    /** @var SeasonService */
    private $seasonService;

    public function __construct(SeasonServiceInterface $seasonService)
    {
        $this->seasonService = $seasonService;
    }

    /**
     * @param Carbon $date
     * @return TimewalkingEvent|null
     * @TODO Support user regions?
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
     * @TODO Support user regions?
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
                    $affixGroups = $this->seasonService->getCurrentSeason($expansion)->affixgroups;
                    $result      = $affixGroups->get(($diffInWeeks % $timewalkingEvent->week_interval) % $affixGroups->count());
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
