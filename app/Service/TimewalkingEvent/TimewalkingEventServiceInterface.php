<?php


namespace App\Service\TimewalkingEvent;

use App\Models\AffixGroup\AffixGroup;
use App\Models\Expansion;
use App\Models\Timewalking\TimewalkingEvent;
use Illuminate\Support\Carbon;

interface TimewalkingEventServiceInterface
{
    function getActiveTimewalkingEventAt(Carbon $date): ?TimewalkingEvent;

    function getAffixGroupAt(Expansion $expansion, Carbon $date): ?AffixGroup;
}
