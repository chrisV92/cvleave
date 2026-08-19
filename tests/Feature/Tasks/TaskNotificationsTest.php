<?php

use App\Models\Project;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\TaskAssigned;
use App\Notifications\TaskCommented;
use App\Notifications\TaskCompleted;
use App\Notifications\TaskDueReminder;
use App\Notifications\WeeklyTaskDigest;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();

    $this->tenant = Tenant::factory()->create();
    $this->admin = User::factory()->for($this->tenant)->admin()->create(['name' => 'Admin']);
    $this->worker = User::factory()->for($this->tenant)->create(['name' => 'Worker']);
    $this->project = Project::factory()->create(['tenant_id' => $this->tenant->id]);
});

it('tells somebody when a task is assigned to them', function () {
    actingInTenant($this->admin);

    $task = Task::factory()->forProject($this->project)->create();
    $task->update(['assignee_id' => $this->worker->id]);

    Notification::assertSentTo($this->worker, TaskAssigned::class);
});

it('says nothing when you assign a task to yourself', function () {
    actingInTenant($this->worker);

    // The rule the whole design rests on: your own actions are not news to
    // you, and being told about them is what makes people mute the channel.
    $task = Task::factory()->forProject($this->project)->create();
    $task->update(['assignee_id' => $this->worker->id]);

    Notification::assertNotSentTo($this->worker, TaskAssigned::class);
});

it('tells the company admins when a task is completed', function () {
    actingInTenant($this->worker);

    $task = Task::factory()->forProject($this->project)->create(['created_by' => $this->admin->id]);
    $done = $this->project->statuses()->where('is_completed', true)->first();

    $task->update(['task_status_id' => $done->id]);

    Notification::assertSentTo($this->admin, TaskCompleted::class);
    Notification::assertNotSentTo($this->worker, TaskCompleted::class);
});

it('does not announce a completion twice to an admin who raised the task', function () {
    actingInTenant($this->worker);

    // The admin is both a company admin and the creator; one message, not two.
    $task = Task::factory()->forProject($this->project)->create(['created_by' => $this->admin->id]);
    $task->update(['task_status_id' => $this->project->statuses()->where('is_completed', true)->first()->id]);

    Notification::assertSentToTimes($this->admin, TaskCompleted::class, 1);
});

it('says nothing again when an already completed task is saved', function () {
    actingInTenant($this->worker);

    $task = Task::factory()->forProject($this->project)->create(['created_by' => $this->admin->id]);
    $done = $this->project->statuses()->where('is_completed', true)->first();
    $task->update(['task_status_id' => $done->id]);

    $task->update(['title' => 'Renamed after the fact']);

    Notification::assertSentToTimes($this->admin, TaskCompleted::class, 1);
});

it('takes a comment to the people on the task but not to its author', function () {
    actingInTenant($this->admin);

    $task = Task::factory()->forProject($this->project)->create([
        'assignee_id' => $this->worker->id,
        'created_by' => $this->admin->id,
    ]);

    $task->comments()->create(['user_id' => $this->admin->id, 'body' => 'Any progress?']);

    Notification::assertSentTo($this->worker, TaskCommented::class);
    Notification::assertNotSentTo($this->admin, TaskCommented::class);
});

it('reminds the assignee the day before a task is due', function () {
    Task::factory()->forProject($this->project)->create([
        'assignee_id' => $this->worker->id,
        'due_date' => today()->addDay(),
    ]);

    $this->artisan('tasks:send-reminders')->assertSuccessful();

    Notification::assertSentTo($this->worker, TaskDueReminder::class);
});

it('chases an overdue task once, then weekly, rather than every day', function () {
    $cases = [1 => true, 2 => false, 6 => false, 7 => true, 8 => false, 14 => true];

    foreach ($cases as $daysLate => $shouldSend) {
        Notification::fake();

        $task = Task::factory()->forProject($this->project)->create([
            'assignee_id' => $this->worker->id,
            'due_date' => today()->subDays($daysLate),
        ]);

        $this->artisan('tasks:send-reminders')->assertSuccessful();

        // Daily would mean twenty-one emails for a task three weeks late.
        $shouldSend
            ? Notification::assertSentTo($this->worker, TaskDueReminder::class)
            : Notification::assertNotSentTo($this->worker, TaskDueReminder::class);

        $task->delete();
    }
});

it('leaves a completed task out of the reminders', function () {
    Task::factory()->forProject($this->project)->create([
        'assignee_id' => $this->worker->id,
        'due_date' => today()->subDay(),
        'task_status_id' => $this->project->statuses()->where('is_completed', true)->first()->id,
    ]);

    $this->artisan('tasks:send-reminders')->assertSuccessful();

    Notification::assertNothingSentTo($this->worker);
});

it('sends nobody a digest when there is nothing to report', function () {
    $this->artisan('tasks:send-weekly-digest')->assertSuccessful();

    Notification::assertNothingSentTo($this->worker);
    Notification::assertNothingSentTo($this->admin);
});

it('sends an employee their own open work and an admin the company view', function () {
    Task::factory()->forProject($this->project)->create([
        'assignee_id' => $this->worker->id,
        'due_date' => today()->subDays(3),
        'title' => 'Late one',
    ]);
    Task::factory()->forProject($this->project)->create(['title' => 'Nobody has this']);

    $this->artisan('tasks:send-weekly-digest')->assertSuccessful();

    Notification::assertSentTo($this->worker, WeeklyTaskDigest::class,
        fn (WeeklyTaskDigest $n) => $n->companyWide === false);

    Notification::assertSentTo($this->admin, WeeklyTaskDigest::class,
        fn (WeeklyTaskDigest $n) => $n->companyWide === true
            // Unassigned work is what an administrator can least afford to miss.
            && collect($n->sections)->contains(fn (array $s) => str_contains(json_encode($s), 'Nobody has this')));
});

it('honours somebody who has switched the weekly summary off', function () {
    $this->worker->update(['notify_weekly_digest' => false]);

    Task::factory()->forProject($this->project)->create([
        'assignee_id' => $this->worker->id,
        'due_date' => today()->subDays(3),
    ]);

    $this->artisan('tasks:send-weekly-digest')->assertSuccessful();

    Notification::assertNotSentTo($this->worker, WeeklyTaskDigest::class);
});

it('keeps the bell for somebody who has switched email off', function () {
    $this->worker->update(['notify_by_email' => false]);

    expect($this->worker->notificationChannels())->toBe(['database'])
        ->and($this->admin->notificationChannels())->toBe(['mail', 'database']);
});
