<?php


namespace App\Traits;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

trait UserCurrentTime
{
    /**
     * @return string
     */
    public function getUserTimezone(): string
    {
        return optional(Auth::user())->timezone ?? config('app.timezone');
    }

    /**
     * @return Carbon Get a date of now with the timezone set properly.
     */
    public function getUserNow(): Carbon
    {
        return Carbon::now($this->getUserTimezone());
    }
}
