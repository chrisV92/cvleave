<?php

namespace App\Services;

use App\Models\Task;
use App\Models\TaskTimeEntry;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Starting and stopping work timers.
 *
 * A person can only be working on one thing at a time, so starting a timer
 * stops whatever else they had running. Without that rule a forgotten timer
 * quietly accumulates hours against a task nobody touched for days, and the
 * totals stop meaning anything.
 */
class TimeTracking
{
    /** The entry this person currently has running, on any task. */
    public function runningFor(User $user): ?TaskTimeEntry
    {
        return TaskTimeEntry::query()
            ->where('user_id', $user->id)
            ->running()
            ->latest('started_at')
            ->first();
    }

    public function isRunningOn(User $user, Task $task): bool
    {
        return $task->timeEntries()
            ->where('user_id', $user->id)
            ->running()
            ->exists();
    }

    /**
     * Begin timing this person's work on a task.
     *
     * Returns the entry that was stopped to make room, if there was one, so
     * the caller can say so rather than leaving it a surprise.
     */
    public function start(User $user, Task $task): ?TaskTimeEntry
    {
        return DB::transaction(function () use ($user, $task) {
            $previous = $this->runningFor($user);

            if ($previous) {
                // Already on this task — nothing to do, and certainly not a
                // second concurrent entry.
                if ($previous->task_id === $task->id) {
                    return null;
                }

                $this->stopEntry($previous);
            }

            TaskTimeEntry::create([
                'task_id' => $task->id,
                'user_id' => $user->id,
                'started_at' => now(),
            ]);

            return $previous;
        });
    }

    /** Stop this person's timer on a task, if one is running. */
    public function stop(User $user, Task $task): ?TaskTimeEntry
    {
        $entry = $task->timeEntries()
            ->where('user_id', $user->id)
            ->running()
            ->latest('started_at')
            ->first();

        if (! $entry) {
            return null;
        }

        return $this->stopEntry($entry);
    }

    protected function stopEntry(TaskTimeEntry $entry): TaskTimeEntry
    {
        $endedAt = now();

        $entry->forceFill([
            'ended_at' => $endedAt,
            // Stored rather than derived, so a later timezone or clock change
            // cannot retroactively alter how long something took.
            'duration_seconds' => max(0, (int) $entry->started_at->diffInSeconds($endedAt)),
        ])->save();

        return $entry;
    }

    /** Seconds each person has logged on a task, most time first. */
    public function perPerson(Task $task): array
    {
        return $task->timeEntries()
            ->with('user')
            ->get()
            ->groupBy('user_id')
            ->map(fn ($entries) => [
                'name' => $entries->first()->user?->name ?? __('Άγνωστος'),
                'seconds' => (int) $entries->sum(fn (TaskTimeEntry $entry) => $entry->seconds()),
            ])
            ->sortByDesc('seconds')
            ->values()
            ->all();
    }
}
