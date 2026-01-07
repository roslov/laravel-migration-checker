Database Migration Checker For Laravel
======================================

This package validates Laravel database migrations. It checks whether all up and down migrations run without errors.

It also validates that down migrations revert all changes, so there is no diff in the database after running them.

It is a Laravel package. It wraps the [Migration Checker](https://github.com/roslov/migration-checker).


## Requirements

- PHP 8.1 or higher,
- Laravel 10.0 or higher


## Limitation

This package currently supports MySQL/MariaDB only.

The console command `migration-checker:check` runs only in the test environment to avoid accidentally affecting the
working database. Therefore, it should always be used with the option `--env=testing`.


## Installation

The package could be installed with composer:

```shell
composer require --dev roslov/laravel-migration-checker
```


## General usage

You can run the command to check your migrations:

```shell
php artisan migration-checker:check --env=testing
```

Be careful to run it in the test environment, otherwise you can damage your data.

Also, ensure that you run this command on an empty database each time.

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
docker network rm meta-network
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

