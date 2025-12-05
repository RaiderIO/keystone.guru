<?php

$providers = [
    App\Providers\AppServiceProvider::class,
    App\Providers\TelescopeServiceProvider::class,
    App\Providers\HelperServiceProvider::class,
    App\Providers\LoggingServiceProvider::class,
    App\Providers\RepositoryServiceProvider::class,
    App\Providers\OctaneServiceProvider::class,
    App\Providers\KeystoneGuruServiceProvider::class,
    App\Providers\ControllerServiceProvider::class,
];

if (app()->environment('local')) {
    $providers[] = App\Providers\HorizonServiceProvider::class;
}

return $providers;
