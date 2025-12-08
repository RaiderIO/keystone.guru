<?php

$providers = [
    App\Providers\AppServiceProvider::class,
    App\Providers\ControllerServiceProvider::class,
    App\Providers\HelperServiceProvider::class,
    App\Providers\KeystoneGuruServiceProvider::class,
    App\Providers\LoggingServiceProvider::class,
    App\Providers\OctaneServiceProvider::class,
    App\Providers\RepositoryServiceProvider::class,
    App\Providers\TelescopeServiceProvider::class,
];

if (app()->environment('local')) {
    $providers[] = App\Providers\HorizonServiceProvider::class;
}

return $providers;
