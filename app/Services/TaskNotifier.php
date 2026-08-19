<?php

namespace App\Services;

use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;
use App\Notifications\TaskAssigned;
use App\Notifications\TaskCommented;
use App\Notifications\TaskCompleted;
use Illuminate\Support\Collection;

/**
 * Who hears about what happens to a task.
 *
 * The single rule that keeps this usable: nobody is told about their own
 * action. Assign a task to yourself and nothing is sent; comment and you do
 * not get your own comment by email. Without it people mute the lot within a
 * week and then miss the ones that mattered.
 */
class TaskNotifier
{
    public function assigned(Task $task, ?User $actor = null): void
    {
        $assignee = $task->assignee;

        if (! $assignee || $this->isActor($assignee, $actor)) {
            return;
        }

        $assignee->notify(new TaskAssigned($task, $actor));
    }

    /**
     * Completion goes to the company's admins and to whoever raised the task —
     * the people waiting on it — minus whoever just finished it.
     */
    public function completed(Task $task, ?User $actor = null): void
    {
        $this->recipients($task, $actor)
            ->each(fn (User $user) => $user->notify(new TaskCompleted($task, $actor)));
    }

    public function commented(TaskComment $comment): void
    {
        $task = $comment->task;

        if (! $task) {
            return;
        }

        // A conversation reaches the people on the task, not the whole company.
        collect([$task->assignee, $task->creator])
            ->filter()
            ->reject(fn (User $user) => $this->isActor($user, $comment->author))
            ->unique('id')
            ->each(fn (User $user) => $user->notify(new TaskCommented($comment)));
    }

    /** @return Collection<int, User> */
    protected function recipients(Task $task, ?User $actor): Collection
    {
        $admins = User::query()
            ->where('tenant_id', $task->tenant_id)
            // Qualified: the roles table carries a tenant_id of its own, so an
            // unqualified column is ambiguous once the subquery joins it.
            ->whereHas('roles', fn ($query) => $query->where('roles.name', 'admin')
                ->where('roles.tenant_id', $task->tenant_id))
            ->get();

        return $admins
            ->push($task->creator)
            ->filter()
            // An admin who is also the creator would otherwise be told twice.
            ->unique('id')
            ->reject(fn (User $user) => $this->isActor($user, $actor))
            ->values();
    }

    protected function isActor(User $user, ?User $actor): bool
    {
        return $actor !== null && $user->getKey() === $actor->getKey();
    }
}
