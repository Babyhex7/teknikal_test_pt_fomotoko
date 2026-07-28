<?php

declare(strict_types=1);

namespace HiddenItem;

/**
 * Orchestrates one play session: prints the board, calculates the probable
 * hiding spots, secretly seats the item on one of them, then lets the
 * player guess coordinates from the terminal until they find it.
 */
final class Game
{
    private Grid $grid;

    /** @var array<int, array{0:int,1:int}> */
    private array $probableLocations;

    /** @var array{0:int,1:int} */
    private array $secretLocation;

    public function __construct(?Grid $grid = null)
    {
        $this->grid = $grid ?? Grid::fromDefaultLayout();
        $this->probableLocations = (new PathFinder($this->grid))->findProbableLocations();

        if ($this->probableLocations === []) {
            throw new \RuntimeException('No reachable clear-path cells found for this grid.');
        }

        $this->secretLocation = $this->probableLocations[array_rand($this->probableLocations)];
    }

    /** @return array<int, array{0:int,1:int}> */
    public function probableLocations(): array
    {
        return $this->probableLocations;
    }

    public function grid(): Grid
    {
        return $this->grid;
    }

    /**
     * Runs the interactive command-line guessing loop.
     *
     * @param resource $input Stream to read guesses from (defaults to STDIN).
     */
    public function play($input = null): void
    {
        $input = $input ?? STDIN;

        echo 'Starting grid:' . PHP_EOL;
        echo $this->grid->render() . PHP_EOL . PHP_EOL;

        echo 'Probable item locations (row,col), reached by moving North then East then South:' . PHP_EOL;
        foreach ($this->probableLocations as [$row, $col]) {
            echo "  ({$row}, {$col})" . PHP_EOL;
        }
        echo PHP_EOL;

        echo 'Grid with probable locations marked as $:' . PHP_EOL;
        echo $this->grid->render($this->probableLocations) . PHP_EOL . PHP_EOL;

        $attempts = 0;
        while (true) {
            echo 'Guess the item location as "row col" (e.g. "1 3"): ';
            $line = fgets($input);

            if ($line === false) {
                echo PHP_EOL . 'No more input, giving up. The item was at '
                    . "({$this->secretLocation[0]}, {$this->secretLocation[1]})." . PHP_EOL;
                return;
            }

            $parts = preg_split('/\s+/', trim($line));
            if (count($parts) !== 2 || !is_numeric($parts[0]) || !is_numeric($parts[1])) {
                echo 'Please enter two numbers separated by a space.' . PHP_EOL;
                continue;
            }

            $attempts++;
            $guess = [(int) $parts[0], (int) $parts[1]];

            if ($guess === $this->secretLocation) {
                echo "Found it in {$attempts} guess(es)! The item was at "
                    . "({$this->secretLocation[0]}, {$this->secretLocation[1]})." . PHP_EOL;
                return;
            }

            echo 'Not there. Try again.' . PHP_EOL;
        }
    }
}
