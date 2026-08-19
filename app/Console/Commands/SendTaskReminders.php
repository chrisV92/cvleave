<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Notifications\TaskDueReminder;
use Illuminate\Console\Command;

class SendTaskReminders extends Command
{
    protected $signature = 'tasks:send-reminders';

    protected $description = 'Remind assignees about tasks due tomorrow or already overdue';

    public function handle(): void
    {
        // Only the assignee. Admins get the same ground covered once a week
        // in the digest; a daily list of somebody else's deadlines is the
        // sort of mail people set up a filter for.
        $due = Task::query()
            ->active()
            ->whereHas('project', fn ($query) => $query->whereNull('archived_at'))
            ->whereNull('completed_at')
            ->whereNotNull('assignee_id')
            ->whereDate('due_date', today()->addDay())
            ->with(['assignee', 'project', 'status'])
            ->get();

        foreach ($due as $task) {
            $task->assignee->notify(new TaskDueReminder($task));
        }

        // Overdue is a standing state, not an event. Sending daily would mean
        // twenty-one emails for a task three weeks late, which trains people
        // to ignore the whole channel. Once when it slips, then weekly.
        $overdue = Task::query()
            ->active()
            ->whereHas('project', fn ($query) => $query->whereNull('archived_at'))
            ->whereNull('completed_at')
            ->whereNotNull('assignee_id')
            ->whereDate('due_date', '<', today())
            ->with(['assignee', 'project', 'status'])
            ->get()
            ->filter(function (Task $task) {
                $daysLate = (int) $task->due_date->startOfDay()->diffInDays(today());

                return $daysLate === 1 || $daysLate % 7 === 0;
            });

        foreach ($overdue as $task) {
            $task->assignee->notify(new TaskDueReminder($task, overdue: true));
        }

        $this->info("Due tomorrow: {$due->count()}, overdue: {$overdue->count()}.");
    }
}
