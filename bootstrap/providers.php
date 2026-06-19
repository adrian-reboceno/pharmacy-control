<?php

use App\Providers\AppServiceProvider;
use App\Providers\HorizonServiceProvider;
use Laravel\Sanctum\SanctumServiceProvider;

return [
    AppServiceProvider::class,
    App\Providers\EventServiceProvider::class,
    HorizonServiceProvider::class,
    SanctumServiceProvider::class,
];
