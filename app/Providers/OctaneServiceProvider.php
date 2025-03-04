<?php

namespace App\Providers;

use App\Repositories\Swoole\EnemyRepositorySwoole;
use App\Repositories\Swoole\FloorRepositorySwoole;
use App\Repositories\Swoole\Interfaces\EnemyRepositorySwooleInterface;
use App\Repositories\Swoole\Interfaces\FloorRepositorySwooleInterface;
use App\Repositories\Swoole\Interfaces\NpcRepositorySwooleInterface;
use App\Repositories\Swoole\Interfaces\SpellRepositorySwooleInterface;
use App\Repositories\Swoole\NpcRepositorySwoole;
use App\Repositories\Swoole\SpellRepositorySwoole;
use Illuminate\Support\ServiceProvider;
use Laravel\Octane\Facades\Octane;


class OctaneServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        parent::register();

        dump('OctaneServiceProvider registered');

        // Swoole
        /**
         * I tried using singletons like below but they always were recreated every single request!
         *
         * $this->app->singleton(EnemyRepositorySwooleInterface::class, EnemyRepositorySwoole::class);
         */
        app()->instance(EnemyRepositorySwooleInterface::class, new EnemyRepositorySwoole());
        app()->instance(FloorRepositorySwooleInterface::class, new FloorRepositorySwoole());
        app()->instance(NpcRepositorySwooleInterface::class, new NpcRepositorySwoole());
        app()->instance(SpellRepositorySwooleInterface::class, new SpellRepositorySwoole());
    }

    public function boot(): void
    {
//        Octane::tick('verify-singleton', function () {
//            $instance = app(EnemyRepositorySwooleInterface::class);
//            dump('Tick: Current instance hash: ' . spl_object_hash($instance));
//        }, 10);
    }
}
