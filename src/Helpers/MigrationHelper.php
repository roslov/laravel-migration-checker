<?php

declare(strict_types=1);

namespace Roslov\LaravelMigrationChecker\Helpers;

use function preg_match_all;

/**
 * Helps handle the artisan migration command results.
 */
final class MigrationHelper
{
    /**
     * Returns pending migrations from the output of `artisan migrate:status`.
     *
     * @param string $output Migration status command output
     *
     * @return string[] Migration filenames
     */
    public function getPendingMigrations(string $output): array
    {
        $found = preg_match_all('/^\s*(\S+)\s\.+.*Pending\s*$/m', $output, $matches);

        return $found ? $matches[1] : [];
    }
}
