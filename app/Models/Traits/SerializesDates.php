<?php


namespace App\Models\Traits;

use Carbon\Carbon;
use DateTimeInterface;
use Eloquent;

/**
 * @mixin Eloquent
 */
trait SerializesDates
{

    /**
     * Prepare a date for array / JSON serialization.
     *
     * @param DateTimeInterface $date
     * @return string
     */
    protected function serializeDate(DateTimeInterface $date)
    {
        if ($date instanceof \Illuminate\Support\Carbon) {
            $date->setTimezone('UTC');
        }
        return $date->format(Carbon::ISO8601);
    }
}