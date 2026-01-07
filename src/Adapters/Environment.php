<?php

declare(strict_types=1);

namespace Roslov\LaravelMigrationChecker\Adapters;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Roslov\MigrationChecker\Contract\EnvironmentInterface;

/**
 * Prepares the database for migration checks.
 */
final class Environment implements EnvironmentInterface
{
    /**
     * Constructor.
     *
     * @param string $database Database
     */
    public function __construct(private readonly string $database)
    {
    }

    /**
     * @inheritDoc
     */
    public function prepare(): void
    {
        $schema = DB::connection($this->database)->getSchemaBuilder();
        if (!$schema->hasTable('migrations')) {
            Artisan::call('migrate:install', [
                '--database' => $this->database,
            ]);
        }
    }

    /**
     * @inheritDoc
     */
    public function cleanUp(): void
    {
        // No-op
    }
}
