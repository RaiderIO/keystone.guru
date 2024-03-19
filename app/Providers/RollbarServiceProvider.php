<?php

namespace App\Providers;

use App\Models\Release;
use App\Service\View\ViewService;
use Illuminate\Support\ServiceProvider;
use Rollbar\Rollbar;

class RollbarServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(ViewService $viewService): void
    {
        $globalViewVariables = $viewService->getGlobalViewVariables();

        /** @var Release $latestRelease */
        $latestRelease = $globalViewVariables['latestRelease'];

        Rollbar::init([
            'access_token' => config('keystoneguru.rollbar.server_access_token'),
            'environment'  => config('app.env'),
            'code_version' => $latestRelease->version,
        ]);
    }
}
