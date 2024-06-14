<?php

namespace RifatH\SiteMapScanner;

use Illuminate\Support\ServiceProvider;
use RifatH\SiteMapScanner\Scanner\Scanner;

class SiteMapScannerServiceProvider extends ServiceProvider
{

    public function boot()
    {
    }

    public function register()
    {
        $this->app->singleton( Scanner::class , function ($url, $depth = 6) {
            return new Scanner($url, $depth);
        });
    }
}
