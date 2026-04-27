<?php

namespace App\Providers;

use App\Services\SettingsService;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use App\View\Components\RequestTimeline;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Manually register components
        Blade::component('request-timeline', RequestTimeline::class);

        View::composer('*', function ($view) {
            $view->with('settings', SettingsService::all());
        });
    }
}
