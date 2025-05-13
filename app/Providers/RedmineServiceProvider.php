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
            $apiUrl = env('REDMINE_API_URL', '');
            
            if (!empty($apiKey) && !empty($apiUrl)) {
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
