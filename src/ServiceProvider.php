<?php

namespace LaravelDev;

use LaravelDev\App\Console\Commands\Cache\MakeDBCacheCommand;
use LaravelDev\App\Console\Commands\Cache\MakeEnumCacheCommand;
use LaravelDev\App\Console\Commands\Cache\MakeRouterCacheCommand;
use LaravelDev\App\Console\Commands\Dump\DumpDBTableModelCommand;
use LaravelDev\App\Console\Commands\Dump\DumpEnumModelCommand;
use LaravelDev\App\Console\Commands\Dump\DumpRouterModelCommand;
use LaravelDev\App\Console\Commands\Dump\DumpTableCommand;
use LaravelDev\App\Console\Commands\GenFiles\GenAllModelsCommand;
use LaravelDev\App\Console\Commands\GenFiles\GenControllerFileCommand;
use LaravelDev\App\Console\Commands\GenFiles\GenEnumFileCommand;
use LaravelDev\App\Console\Commands\GenFiles\GenMigrationFileCommand;
use LaravelDev\App\Console\Commands\GenFiles\GenModelFilesCommand;
use LaravelDev\App\Console\Commands\GenFiles\GenTestFileCommand;
use LaravelDev\App\Console\Commands\Other\DBSeedCommand;
use LaravelDev\App\Console\Commands\Other\RenameMigrationFilesCommand;
use LaravelDev\App\Macros\BuilderMacros;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    /**
     * Bootstrap any package services.
     */
    public function boot(): void
    {
        // register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeDBCacheCommand::class,
                MakeEnumCacheCommand::class,
                MakeRouterCacheCommand::class,

                DumpDBTableModelCommand::class,
                DumpEnumModelCommand::class,
                DumpRouterModelCommand::class,
                DumpTableCommand::class,

                GenAllModelsCommand::class,
                GenControllerFileCommand::class,
                GenEnumFileCommand::class,
                GenMigrationFileCommand::class,
                GenModelFilesCommand::class,
                GenTestFileCommand::class,

                DBSeedCommand::class,
                RenameMigrationFilesCommand::class,
            ]);
        }

        // register macros
        BuilderMacros::boot();

        // register helpers
        require_once(__DIR__ . '/helpers.php');

        // register routes
        $this->loadRoutesFrom(__DIR__ . '/api.php');

        $this->publishes([
            __DIR__ . '/project.php' => config_path("project.php"),
            __DIR__ . '/public/docs' => public_path("docs"),
        ]);
    }
}
