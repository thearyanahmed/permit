<?php

namespace Prophecy\Permit;

use Illuminate\Support\ServiceProvider;

class PermitServiceProvider extends ServiceProvider {

    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        $this->publishes([
	        __DIR__.'/../config.php' => config_path('permit.php'),
	    ]);
    }

    public function register()
    {
    }
}
