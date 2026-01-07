<?php

declare(strict_types=1);

namespace Roslov\LaravelMigrationChecker\Adapters;

use Illuminate\Support\Facades\DB;
use Roslov\MigrationChecker\Contract\QueryInterface;

use function array_map;

/**
 * Fetches data from MySQL.
 */
final class MySqlQuery implements QueryInterface
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
    public function execute(string $query, array $params = []): array
    {
        return array_map(static fn ($row) => (array) $row, DB::connection($this->database)->select($query, $params));
    }
}
