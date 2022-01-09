<?php

namespace Tests;

use App\Models\Season;
use Illuminate\Support\Facades\Artisan;

trait Bootstrap
{
    /**
     * @return void
     */
    private function bootstrap(): void
    {
        Artisan::call('migrate', ['--database' => 'phpunit', '--force' => true]);
        // Only seed if we need to
        if (Season::count() === 0) {
            Artisan::call('db:seed', ['--database' => 'phpunit']);
        }
    }

}
