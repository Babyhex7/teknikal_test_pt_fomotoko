<?php

declare(strict_types=1);

namespace HiddenItem;


final class Grid
{
    public const OBSTACLE = '#';
    public const PATH = '.';
    public const START = 'X';
    public const ITEM_MARKER = '$';

    /** @var array<int, array<int, string>> */
    private array $cells;

    private int $startRow;
    private int $startCol;

    /**
     * @param string[] $rows Raw grid rows, e.g. ["########", "#......#", ...]
     */
    public function __construct(array $rows)
    {
        $this->cells = array_map('str_split', $rows);

        foreach ($this->cells as $row => $line) {
            foreach ($line as $col => $char) {
                if ($char === self::START) {
                    $this->startRow = $row;
                    $this->startCol = $col;
                }
            }
        }

        if (!isset($this->startRow)) {
            throw new \InvalidArgumentException('Grid has no starting position (X).');
        }
    }

    public static function fromDefaultLayout(): self
    {
        return new self([
            '########',
            '#......#',
            '#.###..#',
            '#...#.##',
            '#X#....#',
            '########',
        ]);
    }

    public function height(): int
    {
        return count($this->cells);
    }

    public function width(): int
    {
        return count($this->cells[0]);
    }

    public function startRow(): int
    {
        return $this->startRow;
    }

    public function startCol(): int
    {
        return $this->startCol;
    }

    public function isInside(int $row, int $col): bool
    {
        return $row >= 0 && $row < $this->height() && $col >= 0 && $col < $this->width();
    }

    public function isWalkable(int $row, int $col): bool
    {
        return $this->isInside($row, $col) && $this->cells[$row][$col] !== self::OBSTACLE;
    }

    /**
     * Renders the grid as text. If $marked coordinates are given, those
     * clear-path cells are drawn with the $ symbol instead of '.'.
     *
     * @param array<int, array{0:int,1:int}> $marked
     */
    public function render(array $marked = []): string
    {
        $markedLookup = [];
        foreach ($marked as [$row, $col]) {
            $markedLookup[$row . ',' . $col] = true;
        }

        $lines = [];
        foreach ($this->cells as $row => $line) {
            $out = '';
            foreach ($line as $col => $char) {
                $key = $row . ',' . $col;
                $out .= isset($markedLookup[$key]) && $char === self::PATH
                    ? self::ITEM_MARKER
                    : $char;
            }
            $lines[] = $out;
        }

        return implode(PHP_EOL, $lines);
    }
}
