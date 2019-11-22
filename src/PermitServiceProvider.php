<?php

namespace Prophecy\Permit;

use Illuminate\Support\ServiceProvider;

class PermitServiceProvider extends ServiceProvider {

    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    public function register()
    {
        parent::register();
    }
}
