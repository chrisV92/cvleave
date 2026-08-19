<?php

use App\Models\Project;
use App\Models\Task;
use App\Models\TaskTimeEntry;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TimeTracking;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->tracking = app(TimeTracking::class);
});

it('records a stretch of work from start to stop', function () {
    $tenant = Tenant::factory()->create();
    $project = Project::factory()->create(['tenant_id' => $tenant->id, 'time_tracking_enabled' => true]);
    $task = Task::factory()->forProject($project)->create();
    $user = User::factory()->for($tenant)->create();

    Carbon::setTestNow('2026-08-19 09:00:00');
    $this->tracking->start($user, $task);

    Carbon::setTestNow('2026-08-19 10:30:00');
    $entry = $this->tracking->stop($user, $task);

    expect($entry->duration_seconds)->toBe(5400)
        ->and($task->fresh()->trackedSeconds())->toBe(5400)
        ->and(TaskTimeEntry::humanise(5400))->toBe('1ω 30λ');

    Carbon::setTestNow();
});

it('counts a running timer up to now', function () {
    $tenant = Tenant::factory()->create();
    $project = Project::factory()->create(['tenant_id' => $tenant->id, 'time_tracking_enabled' => true]);
    $task = Task::factory()->forProject($project)->create();
    $user = User::factory()->for($tenant)->create();

    Carbon::setTestNow('2026-08-19 09:00:00');
    $this->tracking->start($user, $task);

    Carbon::setTestNow('2026-08-19 09:45:00');

    expect($task->fresh()->trackedSeconds())->toBe(2700);

    Carbon::setTestNow();
});

it('stops the timer already running when a second one is started', function () {
    $tenant = Tenant::factory()->create();
    $project = Project::factory()->create(['tenant_id' => $tenant->id, 'time_tracking_enabled' => true]);
    $first = Task::factory()->forProject($project)->create(['title' => 'Πρώτη']);
    $second = Task::factory()->forProject($project)->create(['title' => 'Δεύτερη']);
    $user = User::factory()->for($tenant)->create();

    Carbon::setTestNow('2026-08-19 09:00:00');
    $this->tracking->start($user, $first);

    Carbon::setTestNow('2026-08-19 09:20:00');
    $stopped = $this->tracking->start($user, $second);

    // Nobody works on two things at once; a forgotten timer would otherwise
    // keep billing a task nobody has touched.
    expect($stopped?->task_id)->toBe($first->id)
        ->and($first->fresh()->trackedSeconds())->toBe(1200)
        ->and(TaskTimeEntry::query()->running()->count())->toBe(1)
        ->and($this->tracking->isRunningOn($user, $second))->toBeTrue();

    Carbon::setTestNow();
});

it('does not start a second entry for a task already being timed', function () {
    $tenant = Tenant::factory()->create();
    $project = Project::factory()->create(['tenant_id' => $tenant->id, 'time_tracking_enabled' => true]);
    $task = Task::factory()->forProject($project)->create();
    $user = User::factory()->for($tenant)->create();

    $this->tracking->start($user, $task);
    $this->tracking->start($user, $task);

    expect($task->timeEntries()->count())->toBe(1);
});

it('keeps two people\'s timers on the same task apart', function () {
    $tenant = Tenant::factory()->create();
    $project = Project::factory()->create(['tenant_id' => $tenant->id, 'time_tracking_enabled' => true]);
    $task = Task::factory()->forProject($project)->create();

    $eleni = User::factory()->for($tenant)->create(['name' => 'Ελένη']);
    $nikos = User::factory()->for($tenant)->create(['name' => 'Νίκος']);

    Carbon::setTestNow('2026-08-19 09:00:00');
    $this->tracking->start($eleni, $task);
    $this->tracking->start($nikos, $task);

    Carbon::setTestNow('2026-08-19 09:30:00');
    $this->tracking->stop($eleni, $task);

    Carbon::setTestNow('2026-08-19 10:00:00');
    $this->tracking->stop($nikos, $task);

    $perPerson = collect($this->tracking->perPerson($task->fresh()))->keyBy('name');

    expect($perPerson['Νίκος']['seconds'])->toBe(3600)
        ->and($perPerson['Ελένη']['seconds'])->toBe(1800);

    Carbon::setTestNow();
});

it('stops nothing when there was no timer running', function () {
    $tenant = Tenant::factory()->create();
    $project = Project::factory()->create(['tenant_id' => $tenant->id, 'time_tracking_enabled' => true]);
    $task = Task::factory()->forProject($project)->create();
    $user = User::factory()->for($tenant)->create();

    expect($this->tracking->stop($user, $task))->toBeNull()
        ->and($task->timeEntries()->count())->toBe(0);
});
