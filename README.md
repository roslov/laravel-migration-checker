Database Migration Checker For Laravel
======================================

This package validates Laravel database migrations by ensuring every pending migration can be applied and rolled back
without leaving schema differences. It is a Laravel wrapper around
[Migration Checker](https://github.com/roslov/migration-checker) and integrates directly with Artisan, so you can use it
as part of your local workflow or CI.

The checker runs migrations one by one:
1. Creates the migrations table if needed.
2. Takes a schema snapshot.
3. Applies the next pending migration.
4. Rolls the migration back.
5. Compares the schema dump to the original state.

If the schema differs, the command fails and prints a diff so you can pinpoint what was left behind.

## Requirements

- PHP 8.1 or higher,
- Laravel 10.0 or higher


## Limitation

This package currently supports MySQL/MariaDB only.

The console command `migration-checker:check` runs only in the test environment to avoid accidentally affecting the
working database. Therefore, it should always be used with the option `--env=testing`.

The command checks only pending migrations and executes them one at a time. It uses the migration paths in
`database/migrations` and `database/settings` by default.


## Installation

The package could be installed with composer:

```shell
composer require --dev roslov/laravel-migration-checker
```


## General usage

### 1. Ensure a clean test database

Run the checker against a disposable database. It must be empty so the schema snapshot reflects only what your
migrations create.

### 2. Run the checker

You can run the command to check your migrations in the test environment:

```shell
php artisan migration-checker:check --env=testing
```

Be careful to run it in the test environment, otherwise you can damage your data.

You can target a specific connection if you have multiple databases configured:

```shell
php artisan migration-checker:check --env=testing --database=mysql_testing
```

### 3. Review output

The output example of the successful run:
```
[info] Migration check started.
[info] Preparing migration environment...
[info] Checking if another migration can be applied...
[info] Saving the current state...
[info] Applying the up migration...
[info] Applying the down migration...
[info] Saving the state after up and down migrations...
[info] Comparing the states...
[info] The up and down migrations have been applied successfully without any state changes.
[info] Applying the up migration before the next step...
[info] Checking if another migration can be applied...
[info] There are no migrations available.
[info] Cleaning up migration environment...
[info] Migration check completed successfully.
```

The output example of the failed run:
```
[info] Migration check started.
[info] Preparing migration environment...
[info] Checking if another migration can be applied...
[info] Saving the current state...
[info] Applying the up migration...
[info] Applying the down migration...
[info] Saving the state after up and down migrations...
[info] Comparing the states...
[error] The down migration has resulted in a different schema state after rollback.
--- Original
+++ New
@@ @@
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci


+-- Table:
+event
+
+-- Create Table:
+CREATE TABLE `event` (
+  `id` bigint(20) NOT NULL AUTO_INCREMENT,
+  `microtime` double(16,6) NOT NULL COMMENT 'Unix timestamp with microseconds',
+  `producer_name` varchar(64) NOT NULL COMMENT 'Producer name',
+  `body` varchar(4096) NOT NULL COMMENT 'Full message body',
+  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Creation timestamp',
+  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'Update timestamp',
+  PRIMARY KEY (`id`)
+) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Events (transactional outbox)'
+
+
 -- ### Triggers ###


In MigrationChecker.php line 67:

  [Roslov\MigrationChecker\Exception\SchemaDiffersException]
  The up and down migrations have resulted in a different schema state after rollback.
```

If you want to run it in your CI, you can do something like this:

```shell
# Stops the previously running test environment and database (if the previous run failed)
docker stop test-db || true
docker network rm test-network || true
# Prepares the new test environment
docker network create test-network
# Starts the test database
docker run --name test-db --network=test-network -d --rm \
    -e MYSQL_ROOT_PASSWORD=testrootpass \
    -e MYSQL_DATABASE=test \
    -e MYSQL_USER=testuser \
    -e MYSQL_PASSWORD=testpass \
    mysql:8.4.5 --character-set-server=utf8mb4 --collation-server=utf8mb4_0900_ai_ci
# Waits until the database is ready
while ! docker exec test-db \
    mysql --user=testuser --password=testpass \
    -e 'SELECT 1' \
    >/dev/null 2>&1; do
    echo 'Waiting for database connection...'
    sleep 1
done
# Runs the migration check.
# This command should fail if there are problems with migrations
docker run --network=test-network --rm \
    your-project-image:latest \
    php artisan migration-checker:check --env=testing
# Stops the test database
docker stop test-db
# Stops the test environment
docker network rm test-network
```


## Testing

### Unit testing

The package is tested with [PHPUnit](https://phpunit.de/). To run tests:

```shell
./vendor/bin/phpunit
```

### Code style analysis

The code style is analyzed with [PHP_CodeSniffer](https://github.com/squizlabs/PHP_CodeSniffer) and
[PSR-12 Ext coding standard](https://github.com/roslov/psr12ext). To run code style analysis:

```shell
./vendor/bin/phpcs --extensions=php --colors --standard=PSR12Ext --ignore=vendor/* -p -s .
```
