<?php

namespace App\Providers;

use App\Models\AppSetting;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if (request()->header('x-forwarded-proto') === 'https') {
            URL::forceScheme('https');
        }

        // layouts.admin included too so admin previews their own branding live.
        View::composer(['layouts.student', 'layouts.admin'], function ($view) {
            $view->with('appSettings', AppSetting::current());
        });
    }
}
