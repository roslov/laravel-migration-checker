<?php

declare(strict_types=1);

namespace Roslov\LaravelMigrationChecker\Tests\Commands;

use Illuminate\Console\OutputStyle;
use Illuminate\Support\Facades\Facade;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Roslov\LaravelMigrationChecker\Commands\CheckCommand;
use stdClass;
use Symfony\Component\Console\Application as ConsoleApplication;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Throwable;

/**
 * Tests CheckCommand.
 */
#[CoversClass(CheckCommand::class)]
final class CheckCommandTest extends TestCase
{
    /**
     * Tests that the command fails if not in the testing environment.
     */
    public function testFailsIfNotInTestingEnvironment(): void
    {
        $app = $this->createMock(ApplicationMockInterface::class);
        $app->method('environment')
            ->with('testing')
            ->willReturn(false);

        $db = $this->getMockBuilder(stdClass::class)
            ->addMethods(['getDefaultConnection'])
            ->getMock();
        $db->method('getDefaultConnection')->willReturn('mysql');

        $app->method('make')
            ->willReturnCallback(
                static function ($abstract, array $parameters = []) use ($db): MockObject|OutputStyle|null {
                    if ($abstract === 'db') {
                        return $db;
                    }
                    if ($abstract === OutputStyle::class) {
                        return new OutputStyle($parameters['input'], $parameters['output']);
                    }

                    return null;
                },
            );
        $app->method('offsetGet')
            ->willReturnCallback(
                static fn ($abstract): ?MockObject => $abstract === 'db' ? $db : null,
            );
        $app->method('call')
            ->willReturnCallback(
                static fn ($callback) => is_array($callback) ? $callback[0]->{$callback[1]}() : $callback(),
            );

        Facade::setFacadeApplication($app);

        $command = new CheckCommand();
        $command->setLaravel($app);
        $command->setApplication(new ConsoleApplication());

        $input = new ArrayInput([]);
        $output = new BufferedOutput();

        $result = $command->run($input, $output);

        $this->assertEquals(1, $result);
        $this->assertStringContainsString(
            'This command can run only in the test environment. Use option --env=testing',
            $output->fetch(),
        );
    }

    /**
     * Tests that the command proceeds if in the testing environment.
     *
     * We don’t want to test the full logic here, just that it doesn’t fail early.
     * Since the full logic requires a lot of mocks, we’ll just check that it doesn’t fail with the environment error
     * message.
     */
    public function testProceedsIfInTestingEnvironment(): void
    {
        $app = $this->createMock(ApplicationMockInterface::class);
        $app->method('environment')
            ->with('testing')
            ->willReturn(true);

        $db = $this->getMockBuilder(stdClass::class)
            ->addMethods(['getDefaultConnection', 'connection'])
            ->getMock();
        $db->method('getDefaultConnection')->willReturn('mysql');

        $connection = $this->getMockBuilder(stdClass::class)
            ->addMethods(['getSchemaBuilder', 'getPdo', 'getName', 'select'])
            ->getMock();
        $db->method('connection')->willReturn($connection);

        $schemaBuilder = $this->getMockBuilder(stdClass::class)
            ->addMethods(['hasTable'])
            ->getMock();
        $connection->method('getSchemaBuilder')->willReturn($schemaBuilder);

        $app->method('make')
            ->willReturnCallback(
                static function ($abstract, array $parameters = []) use ($db): MockObject|OutputStyle|null {
                    if ($abstract === 'db') {
                        return $db;
                    }
                    if ($abstract === OutputStyle::class) {
                        return new OutputStyle($parameters['input'], $parameters['output']);
                    }

                    return null;
                },
            );
        $app->method('offsetGet')
            ->willReturnCallback(
                static fn ($abstract): ?MockObject => $abstract === 'db' ? $db : null,
            );
        $app->method('call')
            ->willReturnCallback(
                static fn ($callback) => is_array($callback) ? $callback[0]->{$callback[1]}() : $callback(),
            );

        Facade::setFacadeApplication($app);

        $command = new CheckCommand();
        $command->setLaravel($app);
        $command->setApplication(new ConsoleApplication());

        $input = new ArrayInput([]);
        $output = new BufferedOutput();

        // This will probably fail later because we haven’t mocked everything,
        // but we can check it doesn’t fail with the environment error.
        try {
            $command->run($input, $output);
            // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
        } catch (Throwable) {
            // We ignore errors here because we only want to test the environment check,
            // and the full command requires a lot of mocks.
        }

        $this->assertStringNotContainsString(
            'This command can run only in the test environment. Use option --env=testing',
            $output->fetch(),
        );
    }
}
