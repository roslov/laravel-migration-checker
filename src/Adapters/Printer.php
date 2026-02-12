<?php

declare(strict_types=1);

namespace Roslov\LaravelMigrationChecker\Adapters;

use Illuminate\Console\OutputStyle;
use Roslov\MigrationChecker\Contract\PrinterInterface;
use Roslov\MigrationChecker\Contract\StateInterface;
use SebastianBergmann\Diff\Differ;
use SebastianBergmann\Diff\Output\UnifiedDiffOutputBuilder;

use function implode;
use function preg_split;
use function str_starts_with;

/**
 * Prints schema state changes.
 */
final class Printer implements PrinterInterface
{
    /**
     * Color reset (default color)
     */
    private const COLOR_RESET = "\033[0m";

    /**
     * Color: red
     */
    private const COLOR_RED = "\033[31m";

    /**
     * Color: green
     */
    private const COLOR_GREEN = "\033[32m";

    /**
     * Color: cyan
     */
    private const COLOR_CYAN = "\033[36m";

    /**
     * Bold font
     */
    private const COLOR_BOLD = "\033[1m";

    /**
     * Constructor.
     *
     * @param OutputStyle $output Output
     */
    public function __construct(private readonly OutputStyle $output)
    {
    }

    /**
     * @inheritDoc
     */
    public function displayDiff(StateInterface $previousState, StateInterface $currentState): void
    {
        $differ = new Differ(new UnifiedDiffOutputBuilder());
        $diff = $differ->diff($previousState->toString(), $currentState->toString());
        $diff = $this->colorizeUnifiedDiffAnsi($diff);
        $this->output->writeln($diff);
    }

    /**
     * Applies ANSI color codes to a unified diff string for visual enhancement.
     *
     * @param string $diff The unified diff string to be colorized
     *
     * @return string The colorized unified diff string
     */
    private function colorizeUnifiedDiffAnsi(string $diff): string
    {
        $out = [];
        foreach ((array) preg_split("/\r\n|\n|\r/", $diff) as $line) {
            $line = (string) $line;
            $out[] = match (true) {
                str_starts_with($line, '+++ '),
                str_starts_with($line, '--- '),
                str_starts_with($line, '@@') => self::COLOR_BOLD . self::COLOR_CYAN . $line . self::COLOR_RESET,
                str_starts_with($line, '+') => self::COLOR_GREEN . $line . self::COLOR_RESET,
                str_starts_with($line, '-') => self::COLOR_RED . $line . self::COLOR_RESET,
                default => $line,
            };
        }

        return implode(PHP_EOL, $out) . PHP_EOL;
    }
}
