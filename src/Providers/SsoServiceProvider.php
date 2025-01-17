<?php

namespace Mzm\Sso\Providers;

use Illuminate\Support\ServiceProvider;

class SsoServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Gabungkan konfigurasi
        $this->mergeConfigFrom(
            __DIR__ . '/../Config/sso.php',
            'sso'
        );
    }

    public function boot()
    {
        // Publish config
        $this->publishes([
            __DIR__ . '/../Config/sso.php' => config_path('sso.php'),
        ], 'sso-config');

        // Publish the views
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'sso');

        // Optional: Publish views for customization
        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/sso'),
        ], 'sso-views');

        // Load routes
        $this->loadRoutesFrom(__DIR__ . '/../Routes/web.php');
    }
}
