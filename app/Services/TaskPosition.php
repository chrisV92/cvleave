<?php

namespace App\Services;

use App\Models\Task;
use App\Models\TaskStatus;

/**
 * Where a card sits inside its column.
 *
 * Positions are fractional so that dropping a card between two others only
 * writes that one row, instead of renumbering everything below it. Cards are
 * seeded a wide gap apart; each drop between neighbours takes the midpoint.
 *
 * Halving a gap forever eventually runs out of precision, so when the space
 * between two neighbours gets too small the column is renumbered once and the
 * drop is retried. In practice that is rare — it takes roughly thirty
 * consecutive drops into the same shrinking gap to trigger it.
 */
class TaskPosition
{
    /** Distance between freshly numbered cards. */
    public const GAP = 65536.0;

    /** Below this, a midpoint is no longer safely representable. */
    public const MIN_GAP = 0.0001;

    /**
     * The position for a card dropped between two neighbours, either of which
     * may be absent when the card lands at the top or bottom of a column.
     *
     * Returns null when the neighbours are too close together, which is the
     * caller's signal to renumber the column and ask again.
     */
    public static function between(?float $above, ?float $below): ?float
    {
        if ($above === null && $below === null) {
            return self::GAP;
        }

        if ($above === null) {
            return $below - self::GAP;
        }

        if ($below === null) {
            return $above + self::GAP;
        }

        if (abs($below - $above) < self::MIN_GAP) {
            return null;
        }

        return $above + (($below - $above) / 2);
    }

    /** Space a column's cards evenly apart again, preserving their order. */
    public static function rebalance(TaskStatus $status): void
    {
        $status->tasks()
            ->orderBy('position')
            ->orderBy('id')
            ->get()
            ->each(function (Task $task, int $index) {
                // Saved quietly: renumbering is bookkeeping, not a change to
                // the work, and it must not look like the card was touched.
                $task->position = ($index + 1) * self::GAP;
                $task->saveQuietly();
            });
    }

    /** The position that puts a card at the end of a column. */
    public static function endOf(TaskStatus $status): float
    {
        $last = $status->tasks()->max('position');

        return $last === null ? self::GAP : ((float) $last) + self::GAP;
    }
}
