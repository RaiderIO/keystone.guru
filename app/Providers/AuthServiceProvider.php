<?php

namespace App\Providers;

use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Expansion;
use App\Models\LiveSession;
use App\Models\Season;
use App\Models\Tags\Tag;
use App\Models\Tags\TagCategory;
use App\Models\Team;
use App\Policies\DungeonRouteDiscoverDungeonPolicy;
use App\Policies\DungeonRouteDiscoverExpansionPolicy;
use App\Policies\DungeonRouteDiscoverSeasonPolicy;
use App\Policies\DungeonRoutePolicy;
use App\Policies\LiveSessionPolicy;
use App\Policies\TagCategoryPolicy;
use App\Policies\TagPolicy;
use App\Policies\TeamPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        Dungeon::class      => DungeonRouteDiscoverDungeonPolicy::class,
        Expansion::class    => DungeonRouteDiscoverExpansionPolicy::class,
        Season::class       => DungeonRouteDiscoverSeasonPolicy::class,
        DungeonRoute::class => DungeonRoutePolicy::class,
        LiveSession::class  => LiveSessionPolicy::class,
        Tag::class          => TagPolicy::class,
        TagCategory::class  => TagCategoryPolicy::class,
        Team::class         => TeamPolicy::class,
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
