<?php


namespace App\Service\TimewalkingEvent;

use App\Models\Timewalking\TimewalkingEvent;
use App\Models\Timewalking\TimewalkingEventAffixGroup;
use Illuminate\Support\Carbon;

interface TimewalkingEventServiceInterface
{
    function getActiveTimewalkingEventAt(Carbon $date): ?TimewalkingEvent;

    function getTimewalkingEventAffixGroupAt(Carbon $date): ?TimewalkingEventAffixGroup;
}
