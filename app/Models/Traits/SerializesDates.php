<?php

namespace App\Models\Traits;

use DateTimeInterface;
use Eloquent;
use Illuminate\Support\Carbon;

/**
 * @mixin Eloquent
 */
trait SerializesDates
{
    public static string $SERIALIZED_DATE_TIME_FORMAT = 'c';

    /**
     * Prepare a date for array / JSON serialization.
     *
     * @param DateTimeInterface $date
     *
     * @return string
     */
    protected function serializeDate(DateTimeInterface $date): string
    {
        if ($date instanceof Carbon) {
            $date->setTimezone('UTC');
        }

        return $date->format(self::$SERIALIZED_DATE_TIME_FORMAT);
    }

    /**
     * @param $value
     *
     * @return void
     */
    public function setCreatedAtAttribute($value)
    {
        if (is_string($value)) {
            $this->attributes['created_at'] = Carbon::createFromFormat(self::$SERIALIZED_DATE_TIME_FORMAT, $value);
        } else {
            $this->attributes['created_at'] = $value;
        }
    }

    /**
     * @param $value
     *
     * @return void
     */
    public function setUpdatedAtAttribute($value)
    {
        if (is_string($value)) {
            $this->attributes['updated_at'] = Carbon::createFromFormat(self::$SERIALIZED_DATE_TIME_FORMAT, $value);
        } else {
            $this->attributes['updated_at'] = $value;
        }
    }
}