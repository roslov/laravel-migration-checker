<?php

declare(strict_types=1);

namespace Roslov\LaravelMigrationChecker\Adapters;

use Illuminate\Support\Facades\Artisan;
use Psr\Log\LoggerInterface;
use Roslov\LaravelMigrationChecker\Helpers\MigrationHelper;
use Roslov\MigrationChecker\Contract\MigrationInterface;
use Symfony\Component\Console\Output\BufferedOutput;

use function base_path;
use function database_path;
use function file_exists;
use function ltrim;
use function strlen;

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
        private readonly array $migrationPaths = ['migrations', 'settings'],
    ) {
    }

    /**
     * @inheritDoc
     */
    public function canUp(): bool
    {
        return count($this->getPendingMigrations()) > 0;
    }

    /**
     * @inheritDoc
     */
    public function up(): void
    {
        $firstPendingMigration = $this->getPendingMigrations()[0];
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
        $defaultAbsolutePath = database_path($this->migrationPaths[0] . DIRECTORY_SEPARATOR . "$migrationName.php");
        $absolutePath = null;
        foreach ($this->migrationPaths as $migrationPath) {
            $absolutePath = database_path($migrationPath . DIRECTORY_SEPARATOR . "$migrationName.php");
            if (file_exists($absolutePath)) {
                break;
            }
        }

        return ltrim(
            substr($absolutePath ?? $defaultAbsolutePath, strlen(base_path())),
            DIRECTORY_SEPARATOR,
        );
    }
}
