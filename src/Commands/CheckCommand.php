<?php

declare(strict_types=1);

namespace Roslov\LaravelMigrationChecker\Commands;

use Illuminate\Console\Command;
use Roslov\LaravelMigrationChecker\Adapters\Environment;
use Roslov\LaravelMigrationChecker\Adapters\Migration;
use Roslov\LaravelMigrationChecker\Adapters\MySqlQuery;
use Roslov\LaravelMigrationChecker\Adapters\Printer;
use Roslov\LaravelMigrationChecker\Helpers\MigrationHelper;
use Roslov\MigrationChecker\Db\MySqlDump;
use Roslov\MigrationChecker\Db\SchemaStateComparer;
use Roslov\MigrationChecker\MigrationChecker;
use Symfony\Component\Console\Logger\ConsoleLogger;

use function app;
use function config;

/**
 * Command: Checks migrations
 */
final class CheckCommand extends Command
{
    /**
     * @inheritDoc
     */
    protected $signature = <<<'TXT'
        migration-checker:check
                {--database= : DB connection name (default: config(database.default))}
        TXT;

    /**
     * @inheritDoc
     */
    protected $description = 'Checks that each Laravel migration can be applied and rolled back with no schema diff.';

    /**
     * Handles the command.
     *
     * @return int Status code
     */
    public function handle(): int
    {
        $database = $this->option('database') ?: config('database.default');

        if (!app()->environment(['testing'])) {
            $this->error('This command can run only in the test environment. Use option --env=testing');

            return self::FAILURE;
        }

        $logger = new ConsoleLogger($this->output);

        $environment = new Environment($database);
        $migration = new Migration($logger, new MigrationHelper(), $database);
        $printer = new Printer($this->output);
        $query = new MySqlQuery($database);
        $dump = new MySqlDump($query);
        $comparer = new SchemaStateComparer($dump);

        $checker = new MigrationChecker(
            $logger,
            $environment,
            $migration,
            $comparer,
            $printer,
        );

        $checker->check();

        return self::SUCCESS;
    }
}
