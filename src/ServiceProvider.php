<?php

namespace Hsntngr\Repository;

use Hsntngr\Repository\Commands\MakeRepository;
use Hsntngr\Repository\Commands\RepositoryList;
use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;

class ServiceProvider extends IlluminateServiceProvider
{

    public function boot()
    {
        $this->publishes([
            __DIR__ . '/config/repository.php' => config_path('repository.php'),
        ], 'config');

        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeRepository::class,
                RepositoryList::class
            ]);
        }

        $this->loadHelpers();
    }

    public function register()
    {
        $this->app->singleton(IRepositoryManager::class, RepositoryManager::class);
    }

    protected function loadHelpers()
    {
        require_once __DIR__ . "/resources/helpers.php";
    }
}