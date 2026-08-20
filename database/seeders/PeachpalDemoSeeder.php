<?php

namespace Database\Seeders;

use App\Support\CustomFieldType;
use App\Models\CustomField;
use App\Models\Project;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TaskPosition;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;

/**
 * The demo company used for screenshots and for looking at the task manager
 * with something in it.
 *
 * Written as a seeder rather than typed in by hand because this data has been
 * lost to a migrate:fresh once already, and rebuilding it from memory is both
 * slow and lossy. Safe to run repeatedly: everything is matched on a natural
 * key and updated in place.
 */
class PeachpalDemoSeeder extends Seeder
{
    use WithoutModelEvents;

    public const PASSWORD = 'peachpal-demo';

    public function run(): void
    {
        // Model events are suppressed by the trait above, so the company's
        // leave types, board columns and roles are seeded explicitly.
        $tenant = Tenant::firstOrCreate(['slug' => 'peachpal'], ['name' => 'Peachpal']);
        $tenant->seedDefaultLeaveTypes();
        $tenant->seedDefaultTaskStatuses();
        $tenant->seedDefaultRoles();

        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);

        $admin = $this->user($tenant, 'chris.s.varkas@gmail.com', 'Christos S. Varkas', 'admin');
        $worker = $this->user($tenant, 'christos@cvarkas.com', 'Χρήστος Employee', 'employee');

        $platform = $this->project($tenant, $admin, 'Peachpal Πλατφόρμα', '#6366f1');
        $support = $this->project($tenant, $admin, 'Υποστήριξη Πελατών', '#0ea5e9');

        $this->fields($platform);

        // Deliberately spread across columns, owners and deadlines: a board
        // where everything sits in one column shows nothing.
        $this->tasks($platform, $admin, $worker, [
            ['Πραγματικός SMTP provider', 'Νέο', 'worker', 6, 'high'],
            ['Χρονόμετρο και συνημμένα σε εργασίες', 'Σε εξέλιξη', 'worker', 29, 'normal'],
            ['Ρυθμίσεις Cloudflare Tunnel', 'Νέο', 'admin', 12, 'normal'],
            ['Έλεγχος τύπου Α.Ν. 539/1945 από λογιστή', 'Σε εξέλιξη', 'admin', 20, 'urgent'],
            ['Αναβάθμιση σε Filament 5.7', 'Σε έλεγχο', 'admin', null, 'low'],
            ['Ανανέωση screenshots στους οδηγούς', 'Ολοκληρώθηκε', 'worker', -2, 'normal'],
            ['Language switcher στο landing page', 'Ολοκληρώθηκε', 'admin', -5, 'normal'],
        ]);

        $this->tasks($support, $admin, $worker, [
            ['Δεν λαμβάνω το email πρόσκλησης', 'Σε εξέλιξη', 'worker', 1, 'high'],
            ['Αίτημα για εξαγωγή σε Excel ανά τμήμα', 'Νέο', 'worker', -1, 'normal'],
            ['Ερώτηση για μεταφορά υπολοίπου', 'Νέο', 'admin', 4, 'low'],
            ['Λάθος υπόλοιπο μετά από χειροκίνητη ρύθμιση', 'Σε έλεγχο', 'admin', 2, 'urgent'],
            ['Οδηγίες σύνδεσης για νέο υπάλληλο', 'Ολοκληρώθηκε', 'worker', -3, 'normal'],
        ]);
    }

    protected function user(Tenant $tenant, string $email, string $name, string $role): User
    {
        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'tenant_id' => $tenant->id,
                'name' => $name,
                'password' => Hash::make(self::PASSWORD),
                'hire_date' => now()->subYears(3),
            ],
        );

        $user->syncRoles([$role]);

        return $user;
    }

    protected function project(Tenant $tenant, User $owner, string $name, string $color): Project
    {
        $project = Project::updateOrCreate(
            ['tenant_id' => $tenant->id, 'slug' => str($name)->slug()->value()],
            [
                'name' => $name,
                'color' => $color,
                'owner_id' => $owner->id,
                'time_tracking_enabled' => true,
                'attachments_enabled' => true,
            ],
        );

        if ($project->statuses()->doesntExist()) {
            $project->seedStatusesFromCompanyDefaults();
        }

        return $project;
    }

    protected function fields(Project $project): void
    {
        $fields = [
            ['key' => 'effort', 'name' => 'Εκτίμηση (ώρες)', 'type' => CustomFieldType::Number],
            ['key' => 'area', 'name' => 'Περιοχή', 'type' => CustomFieldType::Select,
                'options' => ['Backend', 'Frontend', 'Υποδομή', 'Τεκμηρίωση']],
            ['key' => 'billable', 'name' => 'Χρεώσιμο', 'type' => CustomFieldType::Checkbox],
        ];

        foreach ($fields as $position => $field) {
            CustomField::updateOrCreate(
                ['tenant_id' => $project->tenant_id, 'project_id' => $project->id, 'key' => $field['key']],
                [
                    'name' => $field['name'],
                    'type' => $field['type'],
                    'options' => $field['options'] ?? null,
                    'is_required' => false,
                    'is_active' => true,
                    'position' => $position,
                ],
            );
        }
    }

    /** @param  array<int, array{0: string, 1: string, 2: string, 3: ?int, 4: string}>  $rows */
    protected function tasks(Project $project, User $admin, User $worker, array $rows): void
    {
        foreach ($rows as $row) {
            [$title, $column, $owner, $dueInDays, $priority] = $row;

            $status = $project->statuses()->where('name', $column)->first();

            $task = Task::updateOrCreate(
                ['project_id' => $project->id, 'title' => $title],
                [
                    'tenant_id' => $project->tenant_id,
                    'task_status_id' => $status->id,
                    'assignee_id' => $owner === 'admin' ? $admin->id : $worker->id,
                    'created_by' => $admin->id,
                    'priority' => $priority,
                    'due_date' => $dueInDays === null ? null : today()->addDays($dueInDays),
                    'position' => TaskPosition::endOf($status),
                ],
            );

            // completed_at follows the column through the model's own hook, so
            // a finished demo task carries a real completion date.
            if ($status->is_completed && $task->completed_at === null) {
                $task->forceFill(['completed_at' => now()->subDays(2)])->saveQuietly();
            }
        }
    }
}
