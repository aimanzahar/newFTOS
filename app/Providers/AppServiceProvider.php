<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;

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
        // Register the alias for your custom layout
        Blade::component('layouts.ftadmin.ftadmin-layout', 'ftadmin-layout');
        Blade::component('layouts.ftworker.ftworker-layout', 'ftworker-layout');
    }
}
