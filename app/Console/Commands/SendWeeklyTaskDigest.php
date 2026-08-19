<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\WeeklyTaskDigest;
use App\Support\Permissions;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Spatie\Permission\PermissionRegistrar;

class SendWeeklyTaskDigest extends Command
{
    protected $signature = 'tasks:send-weekly-digest';

    protected $description = 'Send each person a Monday summary of where their tasks stand';

    public function handle(): void
    {
        $sent = 0;

        $registrar = app(PermissionRegistrar::class);

        foreach (Tenant::all() as $tenant) {
            // Permissions are stored per company and fail closed without a
            // company set. A console command has none, so every can() would
            // answer no and the digest would quietly send nothing at all.
            $registrar->setPermissionsTeamId($tenant->id);

            $open = Task::query()
                ->where('tenant_id', $tenant->id)
                ->active()
                ->whereNull('completed_at')
                ->whereHas('project', fn ($query) => $query->whereNull('archived_at'))
                ->with(['project', 'status', 'assignee'])
                ->get();

            $closedLastWeek = Task::query()
                ->where('tenant_id', $tenant->id)
                ->whereNotNull('completed_at')
                ->where('completed_at', '>=', now()->subWeek())
                ->with(['project', 'assignee'])
                ->get();

            foreach (User::where('tenant_id', $tenant->id)->get() as $user) {
                if (! $user->notify_weekly_digest || ! $user->can(Permissions::TASKS_VIEW)) {
                    continue;
                }

                $sent += $user->can(Permissions::PROJECTS_MANAGE)
                    ? $this->sendCompanyDigest($user, $open, $closedLastWeek)
                    : $this->sendPersonalDigest($user, $open, $closedLastWeek);
            }
        }

        $registrar->setPermissionsTeamId(null);

        $this->info("Digests sent: {$sent}.");
    }

    /** What one person is carrying. */
    protected function sendPersonalDigest(User $user, Collection $open, Collection $closed): int
    {
        $mine = $open->where('assignee_id', $user->id);

        $sections = array_values(array_filter([
            $this->section(__('Εκπρόθεσμες'), '#dc2626', $mine->filter(fn (Task $t) => $t->isOverdue())),
            $this->section(__('Αυτή την εβδομάδα'), '#d97706', $mine->filter(
                fn (Task $t) => ! $t->isOverdue() && $t->due_date && $t->due_date->lte(now()->addWeek())
            )),
            $this->section(__('Αργότερα ή χωρίς προθεσμία'), '#71717a', $mine->filter(
                fn (Task $t) => ! $t->isOverdue() && (! $t->due_date || $t->due_date->gt(now()->addWeek()))
            )),
        ]));

        // Nothing to say is a reason not to write. A weekly "you have nothing"
        // teaches people that this email is safe to leave unread.
        if ($sections === []) {
            return 0;
        }

        $done = $closed->where('assignee_id', $user->id)->count();

        $user->notify(new WeeklyTaskDigest(
            $sections,
            __('Έχεις :open ανοιχτές εργασίες. Την περασμένη εβδομάδα ολοκλήρωσες :done.', [
                'open' => $mine->count(),
                'done' => $done,
            ]),
        ));

        return 1;
    }

    /** Where the company stands, by project. */
    protected function sendCompanyDigest(User $user, Collection $open, Collection $closed): int
    {
        $sections = array_values(array_filter([
            $this->section(__('Εκπρόθεσμες'), '#dc2626', $open->filter(fn (Task $t) => $t->isOverdue())),
            // Unassigned work is the thing an administrator can least afford
            // not to see: nobody else is going to notice it.
            $this->section(__('Χωρίς ανάθεση'), '#a855f7', $open->whereNull('assignee_id')),
            $this->section(__('Ολοκληρώθηκαν την περασμένη εβδομάδα'), '#16a34a', $closed),
        ]));

        if ($sections === []) {
            return 0;
        }

        $user->notify(new WeeklyTaskDigest(
            $sections,
            __('Η εταιρεία έχει :open ανοιχτές εργασίες, από τις οποίες :overdue εκπρόθεσμες.', [
                'open' => $open->count(),
                'overdue' => $open->filter(fn (Task $t) => $t->isOverdue())->count(),
            ]),
            companyWide: true,
        ));

        return 1;
    }

    /** @return array{label: string, color: string, tasks: array}|null */
    protected function section(string $label, string $color, Collection|\Illuminate\Support\Collection $tasks): ?array
    {
        if ($tasks->isEmpty()) {
            return null;
        }

        return [
            'label' => $label,
            'color' => $color,
            'tasks' => $tasks->take(15)->map(fn (Task $task) => [
                'title' => $task->title,
                'meta' => collect([
                    $task->project?->name,
                    $task->assignee?->name,
                    $task->due_date?->format('d/m/Y'),
                ])->filter()->implode(' · '),
            ])->values()->all(),
        ];
    }
}
