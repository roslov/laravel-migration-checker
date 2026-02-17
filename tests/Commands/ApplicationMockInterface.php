<?php

declare(strict_types=1);

namespace Roslov\LaravelMigrationChecker\Tests\Commands;

use ArrayAccess;
use Illuminate\Contracts\Foundation\Application;

/**
 * Dummy interface for mocking.
 *
 * @extends ArrayAccess<mixed, mixed>
 */
interface ApplicationMockInterface extends Application, ArrayAccess
{
}
