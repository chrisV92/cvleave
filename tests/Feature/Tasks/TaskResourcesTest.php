<?php

use App\Filament\Resources\Projects\Pages\CreateProject;
use App\Filament\Resources\Projects\Pages\ListProjects;
use App\Filament\Resources\Projects\ProjectResource;
use App\Filament\Resources\Tasks\Pages\CreateTask;
use App\Filament\Resources\Tasks\Pages\ListTasks;
use App\Filament\Resources\Tasks\TaskResource;
use App\Models\Project;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\User;
use Livewire\Livewire;

it('lets everyone in the company see the projects but only admins create them', function () {
    $tenant = Tenant::factory()->create();
    $employee = User::factory()->for($tenant)->create();

    actingInTenant($employee);

    expect(ProjectResource::canViewAny())->toBeTrue()
        ->and(ProjectResource::canCreate())->toBeFalse()
        // Employees still do the work, so tasks stay fully theirs.
        ->and(TaskResource::canViewAny())->toBeTrue()
        ->and(TaskResource::canCreate())->toBeTrue();
});

it('shows a company only its own projects', function () {
    $acme = Tenant::factory()->create();
    $other = Tenant::factory()->create();

    $mine = Project::factory()->create(['tenant_id' => $acme->id, 'name' => 'Δικό μου']);
    $theirs = Project::factory()->create(['tenant_id' => $other->id, 'name' => 'Ξένο']);

    actingInTenant(User::factory()->for($acme)->admin()->create());

    Livewire::test(ListProjects::class)
        // Filament defers loading table rows to a second request, so nothing
        // is rendered until the table is explicitly loaded.
        ->loadTable()
        ->assertCanSeeTableRecords([$mine])
        ->assertCanNotSeeTableRecords([$theirs]);
});

it('shows a company only its own tasks', function () {
    $acme = Tenant::factory()->create();
    $other = Tenant::factory()->create();

    $mine = Task::factory()->forProject(Project::factory()->create(['tenant_id' => $acme->id]))->create();
    $theirs = Task::factory()->forProject(Project::factory()->create(['tenant_id' => $other->id]))->create();

    actingInTenant(User::factory()->for($acme)->admin()->create());

    Livewire::test(ListTasks::class)
        ->loadTable()
        ->assertCanSeeTableRecords([$mine])
        ->assertCanNotSeeTableRecords([$theirs]);
});

it('creates a project with its company, owner and starting columns', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->for($tenant)->admin()->create();

    actingInTenant($admin);

    Livewire::test(CreateProject::class)
        ->fillForm([
            'name' => 'Ανακαίνιση Ιστότοπου',
            'slug' => 'anakainisi',
            'color' => '#6366f1',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $project = Project::where('slug', 'anakainisi')->first();

    expect($project)->not->toBeNull()
        ->and($project->tenant_id)->toBe($tenant->id)
        ->and($project->owner_id)->toBe($admin->id)
        ->and($project->statuses()->count())->toBe(4);
});

it('takes a task\'s company from its project rather than from the session', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->for($tenant)->admin()->create();
    $project = Project::factory()->create(['tenant_id' => $tenant->id]);

    actingInTenant($admin);

    Livewire::test(CreateTask::class)
        ->fillForm([
            'title' => 'Στήσιμο staging',
            'project_id' => $project->id,
            'task_status_id' => $project->defaultStatus()->id,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $task = Task::where('title', 'Στήσιμο staging')->first();

    expect($task->tenant_id)->toBe($tenant->id)
        ->and($task->project_id)->toBe($project->id)
        ->and($task->created_by)->toBe($admin->id);
});

it('refuses a status that belongs to a different project', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->for($tenant)->admin()->create();

    $project = Project::factory()->create(['tenant_id' => $tenant->id]);
    $elsewhere = Project::factory()->create(['tenant_id' => $tenant->id]);

    actingInTenant($admin);

    // The column exists and belongs to the same company, so only the
    // project-scoped option list stands between this and a task sitting in
    // another board's column.
    Livewire::test(CreateTask::class)
        ->fillForm([
            'title' => 'Λάθος στήλη',
            'project_id' => $project->id,
            'task_status_id' => $elsewhere->defaultStatus()->id,
        ])
        ->call('create')
        ->assertHasFormErrors(['task_status_id']);
});

it('hides tasks belonging to an archived project', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->for($tenant)->admin()->create();

    $live = Project::factory()->create(['tenant_id' => $tenant->id]);
    $shelved = Project::factory()->archived()->create(['tenant_id' => $tenant->id]);

    $visible = Task::factory()->forProject($live)->create();
    $hidden = Task::factory()->forProject($shelved)->create();

    actingInTenant($admin);

    Livewire::test(ListTasks::class)
        ->loadTable()
        ->assertCanSeeTableRecords([$visible])
        ->assertCanNotSeeTableRecords([$hidden]);
});

it('offers an employee no way to create or edit a project', function () {
    $tenant = Tenant::factory()->create();
    Project::factory()->create(['tenant_id' => $tenant->id]);

    actingInTenant(User::factory()->for($tenant)->create());

    // Both pages abort with a 403 on their own; the point here is that the
    // buttons leading to them are not offered in the first place.
    Livewire::test(ListProjects::class)
        ->loadTable()
        ->assertActionHidden('create')
        ->assertTableActionHidden('edit', Project::first());
});

it('still refuses an employee who reaches the create page directly', function () {
    $tenant = Tenant::factory()->create();
    $employee = User::factory()->for($tenant)->create();

    actingInTenant($employee);

    $this->get(ProjectResource::getUrl('create', tenant: $tenant))
        ->assertForbidden();
});
