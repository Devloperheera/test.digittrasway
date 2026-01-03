<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Carbon\Carbon;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Set default timezone for Carbon
        Carbon::setLocale('en');
        date_default_timezone_set('Asia/Kolkata');
        
        // Set Carbon default timezone
        Carbon::setToStringFormat('Y-m-d H:i:s');
    }
}
