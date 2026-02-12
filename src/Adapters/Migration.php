<?php

declare(strict_types=1);

namespace Roslov\LaravelMigrationChecker\Adapters;

use Illuminate\Support\Facades\Artisan;
use LogicException;
use Psr\Log\LoggerInterface;
use Roslov\LaravelMigrationChecker\Helpers\MigrationHelper;
use Roslov\MigrationChecker\Contract\MigrationInterface;
use Symfony\Component\Console\Output\BufferedOutput;

use function file_exists;

use const DIRECTORY_SEPARATOR;

/**
 * Handles database migrations.
 */
final class Migration implements MigrationInterface
{
    /**
     * Constructor.
     *
     * @param LoggerInterface $logger Logger
     * @param MigrationHelper $helper Migration helper
     * @param string $database Database
     * @param string[] $migrationPaths Paths where migrations are stored
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly MigrationHelper $helper,
        private readonly string $database,
        private readonly array $migrationPaths = ['database' . DIRECTORY_SEPARATOR . 'migrations'],
    ) {
    }

    /**
     * @inheritDoc
     */
    public function canUp(): bool
    {
        return $this->getPendingMigrations() !== [];
    }

    /**
     * @inheritDoc
     */
    public function up(): void
    {
        $pendingMigrations = $this->getPendingMigrations();
        if ($pendingMigrations === []) {
            throw new LogicException('No pending migrations to apply.');
        }
        $firstPendingMigration = $pendingMigrations[0];
        $this->logger->info(sprintf('Applying the up migration "%s"...', $firstPendingMigration));
        $output = new BufferedOutput();
        Artisan::call('migrate', [
            '--database' => $this->database,
            '--path' => $this->getMigrationPath($firstPendingMigration),
        ], $output);
        echo $output->fetch();
    }

    /**
     * @inheritDoc
     */
    public function down(): void
    {
        $output = new BufferedOutput();
        Artisan::call('migrate:rollback', [
            '--database' => $this->database,
            '--step' => 1,
        ], $output);
        echo $output->fetch();
    }

    /**
     * Returns pending migration filenames.
     *
     * @return string[] Filenames of the pending migrations
     */
    private function getPendingMigrations(): array
    {
        $output = new BufferedOutput();
        Artisan::call('migrate:status', [
            '--database' => $this->database,
        ], $output);

        return $this->helper->getPendingMigrations($output->fetch());
    }

    /**
     * Returns the relative migration path by the migration name.
     */
    private function getMigrationPath(string $migrationName): string
    {
        if ($this->migrationPaths === []) {
            throw new LogicException('There should be at least one migration path.');
        }
        foreach ($this->migrationPaths as $migrationPath) {
            $path = $migrationPath . DIRECTORY_SEPARATOR . "$migrationName.php";
            if (file_exists($path)) {
                return $path;
            }
        }

        return $this->migrationPaths[0] . DIRECTORY_SEPARATOR . "$migrationName.php";
    }
}
