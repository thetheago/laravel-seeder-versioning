<?php

namespace Thetheago\SeederVersioning\Services;

/**
 * Counts added and removed lines between two versions of a file (LCS, like `diff`).
 */
class LineDiff
{
    /**
     * Above this many LCS cells the lines are compared as sets instead, to keep it fast on huge files.
     */
    protected const MAX_CELLS = 4_000_000;

    /**
     * @return array{added: int, removed: int}
     */
    public static function count(string $old, string $new): array
    {
        $oldLines = self::lines($old);
        $newLines = self::lines($new);

        // Common prefix and suffix never count as changes; trimming them keeps the LCS table small.
        while ($oldLines && $newLines && $oldLines[0] === $newLines[0]) {
            array_shift($oldLines);
            array_shift($newLines);
        }

        while ($oldLines && $newLines && end($oldLines) === end($newLines)) {
            array_pop($oldLines);
            array_pop($newLines);
        }

        $common = count($oldLines) * count($newLines) > self::MAX_CELLS
            ? self::commonLinesIgnoringOrder($oldLines, $newLines)
            : self::longestCommonSubsequence($oldLines, $newLines);

        return [
            'added' => count($newLines) - $common,
            'removed' => count($oldLines) - $common,
        ];
    }

    /**
     * @return string[]
     */
    public static function lines(string $content): array
    {
        $content = rtrim(str_replace(["\r\n", "\r"], "\n", $content), "\n");

        return $content === '' ? [] : explode("\n", $content);
    }

    /**
     * @param string[] $a
     * @param string[] $b
     */
    protected static function longestCommonSubsequence(array $a, array $b): int
    {
        $previous = array_fill(0, count($b) + 1, 0);

        foreach ($a as $lineA) {
            $current = [0];

            foreach ($b as $j => $lineB) {
                $current[$j + 1] = $lineA === $lineB
                    ? $previous[$j] + 1
                    : max($previous[$j + 1], $current[$j]);
            }

            $previous = $current;
        }

        return end($previous);
    }

    /**
     * @param string[] $a
     * @param string[] $b
     */
    protected static function commonLinesIgnoringOrder(array $a, array $b): int
    {
        $countsB = array_count_values($b);
        $common = 0;

        foreach (array_count_values($a) as $line => $count) {
            $common += min($count, $countsB[$line] ?? 0);
        }

        return $common;
    }
}
