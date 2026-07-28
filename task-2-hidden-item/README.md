# Task 2 — Hidden Item (CLI Game)

A command-line hidden-item game in plain PHP (no framework/dependencies needed).

## How to run

```bash
php index.php
```

Requires PHP 8.1+ (uses `declare(strict_types=1)` and typed properties).

## How it works

1. **Grid** (`src/Grid.php`) parses the fixed ASCII layout:

   ```
   ########
   #......#
   #.###..#
   #...#.##
   #X#....#
   ########
   ```

   `#` = obstacle, `.` = clear path, `X` = player start. Coordinates are
   `[row, col]`, 0-indexed, row 0 at the top.

2. **PathFinder** (`src/PathFinder.php`) computes every clear-path cell that
   could be the item's location. The player only knows the *order* of
   movement — North (A steps), then East (B steps), then South (C steps) —
   never the exact values of A, B or C. So the algorithm:
   - walks north one step at a time from the start, collecting every cell
     until an obstacle/edge is hit (that's every possible value of A),
   - from each of those cells, walks east the same way (every possible B),
   - from each of *those* cells, walks south the same way (every possible C).

   The union of all cells reached this way is the full set of "probable"
   coordinates — exactly the output required by the task.

3. **Game** (`src/Game.php`) prints the starting grid, the list of probable
   coordinates, the grid with those coordinates marked as `$` (bonus
   requirement), then secretly places the item on one of the candidate cells
   and lets the player guess `row col` pairs from STDIN until they find it.

## Example output

```
Starting grid:
########
#......#
#.###..#
#...#.##
#X#....#
########

Probable item locations (row,col), reached by moving North then East then South:
  (3, 1)
  (3, 2)
  ...

Grid with probable locations marked as $:
########
#$$$$$$#
#$###$$#
#$$$#$##
#X#$.$.#
########

Guess the item location as "row col" (e.g. "1 3"):
```
