<?php

namespace App\Providers;

use App\Repositories\Swoole\DungeonRepositorySwoole;
use App\Repositories\Swoole\EnemyRepositorySwoole;
use App\Repositories\Swoole\FloorRepositorySwoole;
use App\Repositories\Swoole\Interfaces\DungeonRepositorySwooleInterface;
use App\Repositories\Swoole\Interfaces\EnemyRepositorySwooleInterface;
use App\Repositories\Swoole\Interfaces\FloorRepositorySwooleInterface;
use App\Repositories\Swoole\Interfaces\NpcRepositorySwooleInterface;
use App\Repositories\Swoole\Interfaces\SpellRepositorySwooleInterface;
use App\Repositories\Swoole\NpcRepositorySwoole;
use App\Repositories\Swoole\SpellRepositorySwoole;
use Illuminate\Support\ServiceProvider;


class OctaneServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        parent::register();

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
        app()->instance(DungeonRepositorySwooleInterface::class, new DungeonRepositorySwoole());
    }

    public function boot(): void
    {
//        Octane::tick('verify-singleton', function () {
//            $instance = app(EnemyRepositorySwooleInterface::class);
//            dump('Tick: Current instance hash: ' . spl_object_hash($instance));
//        }, 10);
    }
}
