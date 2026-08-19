<?php

use App\Models\Project;
use App\Models\Task;
use App\Models\TaskStatus;
use App\Models\Tenant;
use Illuminate\Database\QueryException;

it('gives a new company a set of default board columns', function () {
    $tenant = Tenant::factory()->create();

    $templates = TaskStatus::where('tenant_id', $tenant->id)->templates()->ordered()->get();

    expect($templates->pluck('name')->all())
        ->toBe(['Νέο', 'Σε εξέλιξη', 'Σε έλεγχο', 'Ολοκληρώθηκε'])
        ->and($templates->firstWhere('is_default', true)?->name)->toBe('Νέο')
        ->and($templates->firstWhere('is_completed', true)?->name)->toBe('Ολοκληρώθηκε');
});

it('copies the company defaults onto a new project', function () {
    $tenant = Tenant::factory()->create();
    $project = Project::factory()->create(['tenant_id' => $tenant->id]);

    expect($project->statuses()->pluck('name')->all())
        ->toBe(['Νέο', 'Σε εξέλιξη', 'Σε έλεγχο', 'Ολοκληρώθηκε']);
});

it('lets one project rename a column without touching the others', function () {
    $tenant = Tenant::factory()->create();
    $sales = Project::factory()->create(['tenant_id' => $tenant->id]);
    $build = Project::factory()->create(['tenant_id' => $tenant->id]);

    $sales->statuses()->where('name', 'Νέο')->first()->update(['name' => 'Lead']);

    expect($sales->statuses()->pluck('name')->all())->toContain('Lead')
        ->and($build->fresh()->statuses()->pluck('name')->all())->toContain('Νέο')
        ->and($build->fresh()->statuses()->pluck('name')->all())->not->toContain('Lead')
        // The company template is untouched, so the next project still gets it.
        ->and(TaskStatus::where('tenant_id', $tenant->id)->templates()->pluck('name')->all())
        ->toContain('Νέο');
});

it('never leaves a project without columns, even for a company created before the feature existed', function () {
    $tenant = Tenant::factory()->create();

    // Simulate a company that predates the Task Manager.
    TaskStatus::where('tenant_id', $tenant->id)->delete();

    $project = Project::factory()->create(['tenant_id' => $tenant->id]);

    expect($project->statuses()->count())->toBe(4)
        ->and($project->defaultStatus()?->name)->toBe('Νέο');
});

it('stamps completed_at when a task reaches a completed column and clears it when it leaves', function () {
    $project = Project::factory()->create();
    $done = $project->statuses()->where('is_completed', true)->first();
    $todo = $project->statuses()->where('is_default', true)->first();

    $task = Task::factory()->forProject($project)->create();
    expect($task->completed_at)->toBeNull();

    $task->update(['task_status_id' => $done->id]);
    expect($task->fresh()->completed_at)->not->toBeNull();

    // Reopening a task has to clear the timestamp, or "finished on" starts
    // lying about work that is back in progress.
    $task->update(['task_status_id' => $todo->id]);
    expect($task->fresh()->completed_at)->toBeNull();
});

it('refuses to delete a column that still holds work', function () {
    $project = Project::factory()->create();
    $status = $project->defaultStatus();

    Task::factory()->forProject($project)->create();

    expect(fn () => $status->delete())
        ->toThrow(QueryException::class);
});

it('takes a project\'s columns and tasks with it when the project is deleted', function () {
    $project = Project::factory()->create();
    Task::factory()->forProject($project)->create();

    $project->delete();

    expect(TaskStatus::where('project_id', $project->id)->count())->toBe(0)
        ->and(Task::where('project_id', $project->id)->count())->toBe(0);
});
