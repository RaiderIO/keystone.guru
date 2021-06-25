<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        'App\Model'                   => 'App\Policies\ModelPolicy',
        'App\Models\DungeonRoute'     => 'App\Policies\DungeonRoutePolicy',
        'App\Models\LiveSession'      => 'App\Policies\LiveSessionPolicy',
        'App\Models\Tags\Tag'         => 'App\Policies\TagPolicy',
        'App\Models\Tags\TagCategory' => 'App\Policies\TagCategoryPolicy',
        'App\Models\Team'             => 'App\Policies\TeamPolicy'
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        //
    }
}
