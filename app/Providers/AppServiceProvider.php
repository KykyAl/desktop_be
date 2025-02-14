<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

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
     */ public function boot()
    {
        $hostname = 'pgserver.local';
        $resolvedIP = gethostbyname($hostname);
        $dbHost = filter_var($resolvedIP, FILTER_VALIDATE_IP) ? $resolvedIP : env('DB_HOST_172.26.8.250', '172.26.8.250');
        Config::set('database.connections.milkyverse_dekstop.host', $dbHost);
    }
}
