<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Force HTTPS in production (behind reverse proxy)
        if ($this->app->environment('local') && str_starts_with(config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        // Register the alias for your custom layout
        Blade::component('layouts.ftadmin.ftadmin-layout', 'ftadmin-layout');
        Blade::component('layouts.ftworker.ftworker-layout', 'ftworker-layout');
    }
}
