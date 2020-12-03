<?php


namespace App\Models\Traits;

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
        return $date->format('c');
    }
}