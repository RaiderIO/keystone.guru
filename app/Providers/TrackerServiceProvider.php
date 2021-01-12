<?php

namespace App\Providers;

use PragmaRX\Tracker\Support\Exceptions\Handler as TrackerExceptionHandler;

class TrackerServiceProvider extends \PragmaRX\Tracker\Vendor\Laravel\ServiceProvider
{
    protected function registerErrorHandler()
    {
        if (config('tracker.log_exceptions')) {
            $illuminateHandler = 'Illuminate\Contracts\Debug\ExceptionHandler';

            $handler = new TrackerExceptionHandler(
                $this->getTracker(),
                $this->app[$illuminateHandler]
            );

            // Replace original Illuminate Exception Handler by Tracker's
            $this->app[$illuminateHandler] = $handler;
        }
    }
}
