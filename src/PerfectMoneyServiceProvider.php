<?php

namespace Selfreliance\PerfectMoney;
use Illuminate\Support\ServiceProvider;

class PerfectMoneyServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
        include __DIR__ . '/routes.php';
        $this->app->make('Selfreliance\PerfectMoney\PerfectMoney');

        $this->publishes([
            __DIR__.'/config/perfectmoney.php' => config_path('perfectmoney.php'),
        ], 'config');
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}