<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Interfaces\RedmineAPIClientInterface;
use App\Repositories\MockRedmineAPIClient;

class RedmineServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(RedmineAPIClientInterface::class, function ($app) {
            $apiKey = env('REDMINE_API_KEY', '');
            
            if (!empty($apiKey)) {
                return new \App\Repositories\RedmineAPIClient();
            }
            
            return new MockRedmineAPIClient();
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
