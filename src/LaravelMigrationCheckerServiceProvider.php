<?php

declare(strict_types=1);

namespace Roslov\LaravelMigrationChecker;

use Illuminate\Support\ServiceProvider;
use Roslov\LaravelMigrationChecker\Commands\CheckCommand;

/**
 * Registers the package.
 */
final class LaravelMigrationCheckerServiceProvider extends ServiceProvider
{
    /**
     * Adds the console command.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                CheckCommand::class,
            ]);
        }
    }
}
