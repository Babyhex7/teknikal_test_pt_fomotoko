<?php

declare(strict_types=1);

namespace HiddenItem;

/**
 * Computes every clear-path cell that is reachable by moving
 * Up (North) A steps, then Right (East) B steps, then Down (South) C steps
 * from the starting position, where A, B and C are unknown positive
 * integers bounded only by the grid's obstacles/edges.
 *
 * Because the exact step counts are never revealed to the player, the
 * result is not a single coordinate but the full set of coordinates that
 * *could* be the final cell for some valid combination of A, B and C.
 * That set is exactly the "probable coordinate points" the task asks for.
 */
final class PathFinder
{
    public function __construct(private readonly Grid $grid)
    {
    }

    /**
     * @return array<int, array{0:int,1:int}> Unique [row, col] candidates.
     */
    public function findProbableLocations(): array
    {
        $start = [$this->grid->startRow(), $this->grid->startCol()];

        // Phase 1: every cell reachable by walking north 0..N steps.
        $afterUp = $this->walk($start, -1, 0);

        // Phase 2: from each of those cells, every cell reachable walking east.
        $afterRight = [];
        foreach ($afterUp as $cell) {
            $afterRight = array_merge($afterRight, $this->walk($cell, 0, 1));
        }
        $afterRight = $this->unique($afterRight);

        // Phase 3: from each of those cells, every cell reachable walking south.
        $afterDown = [];
        foreach ($afterRight as $cell) {
            $afterDown = array_merge($afterDown, $this->walk($cell, 1, 0));
        }
        $afterDown = $this->unique($afterDown);

        // The item can never be sitting on the starting tile.
        return array_values(array_filter(
            $afterDown,
            fn (array $cell) => $cell !== $start
        ));
    }

    /**
     * Walks in a single direction from $from, one step at a time, stopping
     * as soon as an obstacle or the grid edge is hit. Returns every cell
     * visited along the way (including 0 steps, i.e. $from itself), since
     * the step count for that leg of the journey is unknown.
     *
     * @param array{0:int,1:int} $from
     * @return array<int, array{0:int,1:int}>
     */
    private function walk(array $from, int $rowDelta, int $colDelta): array
    {
        $visited = [$from];
        [$row, $col] = $from;

        while (true) {
            $row += $rowDelta;
            $col += $colDelta;

            if (!$this->grid->isWalkable($row, $col)) {
                break;
            }

            $visited[] = [$row, $col];
        }

        return $visited;
    }

    /**
     * @param array<int, array{0:int,1:int}> $cells
     * @return array<int, array{0:int,1:int}>
     */
    private function unique(array $cells): array
    {
        $seen = [];
        $result = [];

        foreach ($cells as $cell) {
            $key = $cell[0] . ',' . $cell[1];
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $result[] = $cell;
            }
        }

        return $result;
    }
}
