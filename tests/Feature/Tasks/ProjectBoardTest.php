<?php

use App\Filament\Resources\Projects\Pages\ProjectBoard;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskStatus;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TaskPosition;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

function board(Project $project): Testable
{
    return Livewire::test(ProjectBoard::class, ['record' => $project->getRouteKey()]);
}

function orderIn(TaskStatus $status): array
{
    return $status->tasks()->orderBy('position')->orderBy('id')->pluck('title')->all();
}

it('moves a card to another column', function () {
    $tenant = Tenant::factory()->create();
    $project = Project::factory()->create(['tenant_id' => $tenant->id]);
    $todo = $project->defaultStatus();
    $doing = $project->statuses()->where('name', 'Σε εξέλιξη')->first();

    $task = Task::factory()->forProject($project)->create(['title' => 'Α']);

    actingInTenant(User::factory()->for($tenant)->admin()->create());

    board($project)->call('moveTask', $task->id, $doing->id);

    expect($task->fresh()->task_status_id)->toBe($doing->id);
});

it('places a card between the two it was dropped between', function () {
    $tenant = Tenant::factory()->create();
    $project = Project::factory()->create(['tenant_id' => $tenant->id]);
    $todo = $project->defaultStatus();

    $first = Task::factory()->forProject($project)->create(['title' => 'Πρώτο', 'position' => 1000]);
    $second = Task::factory()->forProject($project)->create(['title' => 'Δεύτερο', 'position' => 2000]);
    $mover = Task::factory()->forProject($project)->create(['title' => 'Μεσαίο', 'position' => 9000]);

    actingInTenant(User::factory()->for($tenant)->admin()->create());

    board($project)->call('moveTask', $mover->id, $todo->id, $first->id, $second->id);

    expect(orderIn($todo))->toBe(['Πρώτο', 'Μεσαίο', 'Δεύτερο']);
});

it('marks a card completed when it is dropped into a completed column', function () {
    $tenant = Tenant::factory()->create();
    $project = Project::factory()->create(['tenant_id' => $tenant->id]);
    $done = $project->statuses()->where('is_completed', true)->first();

    $task = Task::factory()->forProject($project)->create();

    actingInTenant(User::factory()->for($tenant)->admin()->create());

    board($project)->call('moveTask', $task->id, $done->id);

    expect($task->fresh()->completed_at)->not->toBeNull();
});

it('refuses a column belonging to a different project', function () {
    $tenant = Tenant::factory()->create();
    $project = Project::factory()->create(['tenant_id' => $tenant->id]);
    $elsewhere = Project::factory()->create(['tenant_id' => $tenant->id]);

    $task = Task::factory()->forProject($project)->create();
    $foreignColumn = $elsewhere->defaultStatus();

    actingInTenant(User::factory()->for($tenant)->admin()->create());

    // The column exists and belongs to the same company — only the lookup
    // through this project stands between a crafted call and a task sitting on
    // somebody else's board.
    board($project)->call('moveTask', $task->id, $foreignColumn->id)
        ->assertStatus(404);

    expect($task->fresh()->task_status_id)->not->toBe($foreignColumn->id);
});

it('refuses a card belonging to a different company', function () {
    $acme = Tenant::factory()->create();
    $other = Tenant::factory()->create();

    $project = Project::factory()->create(['tenant_id' => $acme->id]);
    $theirTask = Task::factory()->forProject(Project::factory()->create(['tenant_id' => $other->id]))->create();

    actingInTenant(User::factory()->for($acme)->admin()->create());

    board($project)->call('moveTask', $theirTask->id, $project->defaultStatus()->id)
        ->assertStatus(404);
});

it('refuses to move anything for someone who may only look', function () {
    $tenant = Tenant::factory()->create();
    $project = Project::factory()->create(['tenant_id' => $tenant->id]);
    $task = Task::factory()->forProject($project)->create();
    $doing = $project->statuses()->where('name', 'Σε εξέλιξη')->first();

    // A role that can see the boards but not work on them.
    $viewer = User::factory()->for($tenant)->create();
    actingInTenant($viewer);
    $viewer->revokePermissionTo('tasks.manage');
    $viewer->roles()->first()?->revokePermissionTo('tasks.manage');
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    board($project)->call('moveTask', $task->id, $doing->id)
        ->assertStatus(403);

    expect($task->fresh()->task_status_id)->not->toBe($doing->id);
});

it('renumbers a column when two cards run out of room between them', function () {
    $tenant = Tenant::factory()->create();
    $project = Project::factory()->create(['tenant_id' => $tenant->id]);
    $todo = $project->defaultStatus();

    // Adjacent positions closer together than a midpoint can be taken.
    $above = Task::factory()->forProject($project)->create(['title' => 'Πάνω', 'position' => 1.0]);
    $below = Task::factory()->forProject($project)->create(['title' => 'Κάτω', 'position' => 1.00001]);
    $mover = Task::factory()->forProject($project)->create(['title' => 'Ανάμεσα', 'position' => 500]);

    actingInTenant(User::factory()->for($tenant)->admin()->create());

    board($project)->call('moveTask', $mover->id, $todo->id, $above->id, $below->id);

    expect(orderIn($todo))->toBe(['Πάνω', 'Ανάμεσα', 'Κάτω'])
        // Renumbered, so there is room to drop between them again.
        ->and((float) $below->fresh()->position - (float) $above->fresh()->position)
        ->toBeGreaterThan(TaskPosition::MIN_GAP);
});

it('shows only its own project\'s columns and cards', function () {
    $tenant = Tenant::factory()->create();
    $mine = Project::factory()->create(['tenant_id' => $tenant->id]);
    $other = Project::factory()->create(['tenant_id' => $tenant->id]);

    Task::factory()->forProject($mine)->create(['title' => 'Δικό μου']);
    Task::factory()->forProject($other)->create(['title' => 'Ξένο']);

    actingInTenant(User::factory()->for($tenant)->admin()->create());

    board($mine)
        ->assertSee('Δικό μου')
        ->assertDontSee('Ξένο');
});

it('keeps an employee from opening another company\'s board', function () {
    $acme = Tenant::factory()->create();
    $other = Tenant::factory()->create();
    $theirProject = Project::factory()->create(['tenant_id' => $other->id]);

    actingInTenant(User::factory()->for($acme)->create());

    // Filament's tenant scoping means the record cannot even be resolved.
    expect(fn () => board($theirProject))
        ->toThrow(ModelNotFoundException::class);
});
