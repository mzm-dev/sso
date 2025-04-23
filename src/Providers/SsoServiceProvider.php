<?php

namespace Mzm\Sso\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Log;

class SsoServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Gabungkan konfigurasi
        $this->mergeConfigFrom(__DIR__ . '/../Config/sso.php', 'sso');

        // Merge log config supaya tersedia sebagai 'logging.channels.sso_log'
        $this->mergeConfigFrom(__DIR__ . '/../Config/sso_log.php', 'logging.channels.sso_log');
    }

    public function boot()
    {

        // Auto create log folder
        $path = storage_path('logs/sso');
        if (!file_exists($path)) {
            mkdir($path, 0775, true);
        }

        // Log init
        Log::channel('sso_log')->info('[SSO] Bootstrapped SsoServiceProvider.');


        // Publish config
        $this->publishes([
            __DIR__ . '/../Config/sso.php' => config_path('sso.php'),
        ], 'sso-config');

        // Daftarkan Blade component
        Blade::componentNamespace('Mzm\\Sso\\View\\Components', 'sso');

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
