<?php

declare(strict_types=1);

namespace Roslov\LaravelMigrationChecker\Tests\Helpers;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Roslov\LaravelMigrationChecker\Helpers\MigrationHelper;

/**
 * Tests MigrationHelper
 */
#[CoversClass(MigrationHelper::class)]
final class MigrationHelperTest extends TestCase
{
    /**
     * Tests pending migration extraction.
     *
     * @param string $output Migration status command output
     * @param string[] $expected Expected migration filenames
     */
    #[DataProvider('statusProvider')]
    public function testGetPendingMigrations(string $output, array $expected): void
    {
        $helper = new MigrationHelper();
        $this->assertEquals($expected, $helper->getPendingMigrations($output));
    }

    /**
     * Returns test cases for pending migration extraction.
     *
     * @return array{0: string, 1: string[]}[] Test cases
     */
    public static function statusProvider(): array
    {
        return [
            'no migrations' => [
                '',
                [],
            ],
            'no migrations with header' => [
                <<<'TXT'
                      Migration name ............................... Batch / Status
                    TXT,
                [],
            ],
            'no pending migrations' => [
                // Two spaces are important at the beginning of each line
                <<<'TXT'
                      Migration name ............................... Batch / Status
                      0001_01_01_000000_migration_abc ..................... [1] Ran
                      0001_01_01_000001_migration_efd ..................... [2] Ran
                      0001_01_01_000002_another_migration ................. [3] Ran
                      2025_01_01_000001_create_something .................. [4] Ran
                      2025_01_01_000002_remove_something .................. [5] Ran
                      2025_01_01_000003_migration_1 ....................... [6] Ran
                      2025_01_02_000002_migration_2 ....................... [7] Ran
                      2025_01_02_000003_test .............................. [8] Ran
                      2025_01_02_000004_insert_data ....................... [9] Ran
                      2025_07_23_150414_rollback_of_something ............ [10] Ran
                      2025_07_24_122711_migration_efg .................... [11] Ran
                      2025_07_29_084338_add_columns ...................... [12] Ran
                    TXT,
                [],
            ],
            'last pending migration' => [
                <<<'TXT'
                      Migration name ............................... Batch / Status
                      0001_01_01_000000_migration_abc ..................... [1] Ran
                      0001_01_01_000001_migration_efd ..................... [2] Ran
                      0001_01_01_000002_another_migration ................. [3] Ran
                      2025_01_01_000001_create_something .................. [4] Ran
                      2025_01_01_000002_remove_something .................. [5] Ran
                      2025_01_01_000003_migration_1 ....................... [6] Ran
                      2025_01_02_000002_migration_2 ....................... [7] Ran
                      2025_01_02_000003_test .............................. [8] Ran
                      2025_01_02_000004_insert_data ....................... [9] Ran
                      2025_07_23_150414_rollback_of_something ............ [10] Ran
                      2025_07_24_122711_migration_efg .................... [11] Ran
                      2025_07_29_084338_add_columns ....................... Pending
                    TXT,
                [
                    '2025_07_29_084338_add_columns',
                ],
            ],
            'several pending migrations at the end' => [
                <<<'TXT'
                      Migration name ............................... Batch / Status
                      0001_01_01_000000_migration_abc ..................... [1] Ran
                      0001_01_01_000001_migration_efd ..................... [2] Ran
                      0001_01_01_000002_another_migration ................. [3] Ran
                      2025_01_01_000001_create_something .................. [4] Ran
                      2025_01_01_000002_remove_something .................. [5] Ran
                      2025_01_01_000003_migration_1 ....................... [6] Ran
                      2025_01_02_000002_migration_2 ....................... [7] Ran
                      2025_01_02_000003_test .............................. [8] Ran
                      2025_01_02_000004_insert_data ....................... Pending
                      2025_07_23_150414_rollback_of_something ............. Pending
                      2025_07_24_122711_migration_efg ..................... Pending
                      2025_07_29_084338_add_columns ....................... Pending
                    TXT,
                [
                    '2025_01_02_000004_insert_data',
                    '2025_07_23_150414_rollback_of_something',
                    '2025_07_24_122711_migration_efg',
                    '2025_07_29_084338_add_columns',
                ],
            ],
            'not last pending migrations' => [
                <<<'TXT'
                      Migration name ............................... Batch / Status
                      0001_01_01_000000_migration_abc ..................... [1] Ran
                      0001_01_01_000001_migration_efd ..................... [2] Ran
                      0001_01_01_000002_another_migration ................. [3] Ran
                      2025_01_01_000001_create_something .................. Pending
                      2025_01_01_000002_remove_something .................. [4] Ran
                      2025_01_01_000003_migration_1 ....................... Pending
                      2025_01_02_000002_migration_2 ....................... Pending
                      2025_01_02_000003_test .............................. [5] Ran
                      2025_01_02_000004_insert_data ....................... [6] Ran
                      2025_07_23_150414_rollback_of_something ............. [7] Ran
                      2025_07_24_122711_migration_efg ..................... [8] Ran
                      2025_07_29_084338_add_columns ....................... [9] Ran
                    TXT,
                [
                    '2025_01_01_000001_create_something',
                    '2025_01_01_000003_migration_1',
                    '2025_01_02_000002_migration_2',
                ],
            ],
            'all migrations pending are pending' => [
                <<<'TXT'
                      Migration name ............................... Batch / Status
                      0001_01_01_000000_migration_abc ..................... Pending
                      0001_01_01_000001_migration_efd ..................... Pending
                      0001_01_01_000002_another_migration ................. Pending
                      2025_01_01_000001_create_something .................. Pending
                      2025_01_01_000002_remove_something .................. Pending
                      2025_01_01_000003_migration_1 ....................... Pending
                      2025_01_02_000002_migration_2 ....................... Pending
                      2025_01_02_000003_test .............................. Pending
                      2025_01_02_000004_insert_data ....................... Pending
                      2025_07_23_150414_rollback_of_something ............. Pending
                      2025_07_24_122711_migration_efg ..................... Pending
                      2025_07_29_084338_add_columns ....................... Pending
                    TXT,
                [
                    '0001_01_01_000000_migration_abc',
                    '0001_01_01_000001_migration_efd',
                    '0001_01_01_000002_another_migration',
                    '2025_01_01_000001_create_something',
                    '2025_01_01_000002_remove_something',
                    '2025_01_01_000003_migration_1',
                    '2025_01_02_000002_migration_2',
                    '2025_01_02_000003_test',
                    '2025_01_02_000004_insert_data',
                    '2025_07_23_150414_rollback_of_something',
                    '2025_07_24_122711_migration_efg',
                    '2025_07_29_084338_add_columns',
                ],
            ],
        ];
    }
}
