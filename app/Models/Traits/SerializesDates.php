<?php

namespace App\Models\Traits;

use Carbon\Exceptions\InvalidFormatException;
use DateTimeInterface;
use Eloquent;
use Illuminate\Support\Carbon;

/**
 * @mixin Eloquent
 */
trait SerializesDates
{
    public static string $SERIALIZED_DATE_TIME_FORMAT = 'c';
    public static string $DATABASE_DATE_TIME_FORMAT   = 'Y-m-d H:i:s';

    /**
     * Prepare a date for array / JSON serialization.
     */
    protected function serializeDate(DateTimeInterface $date): string
    {
        if ($date instanceof Carbon) {
            $date->setTimezone('UTC');
        }

        return $date->format(self::$SERIALIZED_DATE_TIME_FORMAT);
    }

    public function setCreatedAtAttribute($value): void
    {
        if (is_string($value)) {
            try {
                $this->attributes['created_at'] = Carbon::createFromFormat(self::$SERIALIZED_DATE_TIME_FORMAT, $value);
            } catch (InvalidFormatException $exception) {
                $this->attributes['created_at'] = Carbon::createFromFormat(self::$DATABASE_DATE_TIME_FORMAT, $value);
            }
        } else {
            $this->attributes['created_at'] = $value;
        }
    }

    public function setUpdatedAtAttribute($value): void
    {
        if (is_string($value)) {
            try {
                $this->attributes['created_at'] = Carbon::createFromFormat(self::$SERIALIZED_DATE_TIME_FORMAT, $value);
            } catch (InvalidFormatException $exception) {
                $this->attributes['created_at'] = Carbon::createFromFormat(self::$DATABASE_DATE_TIME_FORMAT, $value);
            }
        } else {
            $this->attributes['updated_at'] = $value;
        }
    }
}
