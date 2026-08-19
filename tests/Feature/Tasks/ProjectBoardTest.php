<?php

use App\Filament\Resources\Projects\Pages\ProjectBoard;
use App\Filament\Resources\Projects\Schemas\BoardTaskForm;
use App\Models\CustomField;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskStatus;
use App\Models\Tenant;
use App\Models\User;
use App\Services\CustomFieldSchema;
use App\Services\TaskPosition;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

it('edits a card from the board without leaving it', function () {
    $tenant = Tenant::factory()->create();
    $project = Project::factory()->create(['tenant_id' => $tenant->id]);
    $review = $project->statuses()->where('name', 'Σε έλεγχο')->first();
    $task = Task::factory()->forProject($project)->create(['title' => 'Πριν']);

    $field = CustomField::factory()->create(['tenant_id' => $tenant->id, 'key' => 'notes', 'name' => 'Σημείωση']);
    $assignee = User::factory()->for($tenant)->create();

    actingInTenant(User::factory()->for($tenant)->admin()->create());

    board($project)->callAction('editTask', [
        'title' => 'Μετά',
        'task_status_id' => $review->id,
        'assignee_id' => $assignee->id,
        'priority' => Task::PRIORITY_HIGH,
        CustomFieldSchema::STATE_KEY => [$field->id => 'μια σημείωση'],
    ], ['task' => $task->id]);

    $task->refresh();

    expect($task->title)->toBe('Μετά')
        ->and($task->task_status_id)->toBe($review->id)
        ->and($task->assignee_id)->toBe($assignee->id)
        ->and($task->customFieldState()[$field->id])->toBe('μια σημείωση');
});

it('hands the browser a freshly rendered card after an edit', function () {
    $tenant = Tenant::factory()->create();
    $project = Project::factory()->create(['tenant_id' => $tenant->id]);
    $task = Task::factory()->forProject($project)->create(['title' => 'Παλιός τίτλος']);

    actingInTenant(User::factory()->for($tenant)->admin()->create());

    // The board is wire:ignore'd, so a save can never repaint it — the card
    // markup has to come back over the wire or the panel would close onto a
    // stale card.
    board($project)
        ->callAction('editTask', [
            'title' => 'Νέος τίτλος',
            'task_status_id' => $task->task_status_id,
        ], ['task' => $task->id])
        ->assertDispatched('board-card-updated', fn (string $event, array $data) => $data['id'] === $task->id
            && str_contains($data['html'], 'Νέος τίτλος'));
});

it('refuses to edit a card belonging to another project', function () {
    $tenant = Tenant::factory()->create();
    $project = Project::factory()->create(['tenant_id' => $tenant->id]);
    $elsewhere = Project::factory()->create(['tenant_id' => $tenant->id]);
    $theirTask = Task::factory()->forProject($elsewhere)->create(['title' => 'Ξένη']);

    actingInTenant(User::factory()->for($tenant)->admin()->create());

    board($project)->callAction('editTask', [
        'title' => 'Αλλαγμένη',
        'task_status_id' => $project->defaultStatus()->id,
    ], ['task' => $theirTask->id])->assertStatus(403);

    expect($theirTask->fresh()->title)->toBe('Ξένη');
});

it('refuses a column from another project when saving the panel', function () {
    $tenant = Tenant::factory()->create();
    $project = Project::factory()->create(['tenant_id' => $tenant->id]);
    $elsewhere = Project::factory()->create(['tenant_id' => $tenant->id]);
    $task = Task::factory()->forProject($project)->create(['title' => 'Δική μου']);

    actingInTenant(User::factory()->for($tenant)->admin()->create());

    // Two things stop this. The column select only offers this project's
    // statuses, so validation rejects it first; behind that the action aborts
    // outright, for a submission that skips the form. The assertion is on the
    // outcome rather than on which layer caught it.
    board($project)->callAction('editTask', [
        'title' => 'Δική μου',
        'task_status_id' => $elsewhere->defaultStatus()->id,
    ], ['task' => $task->id])->assertHasActionErrors(['task_status_id']);

    expect($task->fresh()->task_status_id)->not->toBe($elsewhere->defaultStatus()->id);
});

it('creates a task straight into the column the plus was pressed on', function () {
    $tenant = Tenant::factory()->create();
    $project = Project::factory()->create(['tenant_id' => $tenant->id]);
    $review = $project->statuses()->where('name', 'Σε έλεγχο')->first();

    actingInTenant($admin = User::factory()->for($tenant)->admin()->create());

    board($project)->callAction('createTask', [
        'title' => 'Από το board',
        'task_status_id' => $review->id,
        'priority' => Task::PRIORITY_NORMAL,
    ], ['column' => $review->id]);

    $task = Task::where('title', 'Από το board')->first();

    expect($task)->not->toBeNull()
        ->and($task->task_status_id)->toBe($review->id)
        ->and($task->project_id)->toBe($project->id)
        // From the project, never the session.
        ->and($task->tenant_id)->toBe($tenant->id)
        ->and($task->created_by)->toBe($admin->id);
});

it('defaults a new task to the column the plus names', function () {
    $tenant = Tenant::factory()->create();
    $project = Project::factory()->create(['tenant_id' => $tenant->id]);
    $review = $project->statuses()->where('name', 'Σε έλεγχο')->first();

    actingInTenant(User::factory()->for($tenant)->admin()->create());

    board($project)
        ->mountAction('createTask', ['column' => $review->id])
        ->assertActionDataSet(['task_status_id' => $review->id]);
});

it('hands the browser the new card so the board shows it without a reload', function () {
    $tenant = Tenant::factory()->create();
    $project = Project::factory()->create(['tenant_id' => $tenant->id]);
    $todo = $project->defaultStatus();

    actingInTenant(User::factory()->for($tenant)->admin()->create());

    board($project)
        ->callAction('createTask', [
            'title' => 'Ολοκαίνουρια',
            'task_status_id' => $todo->id,
        ], ['column' => $todo->id])
        ->assertDispatched('board-card-added', fn (string $event, array $data) => $data['columnId'] === $todo->id
            && str_contains($data['html'], 'Ολοκαίνουρια'));
});

it('offers no way to add a task to someone who may only look', function () {
    $tenant = Tenant::factory()->create();
    $project = Project::factory()->create(['tenant_id' => $tenant->id]);

    $viewer = User::factory()->for($tenant)->create();
    actingInTenant($viewer);
    $viewer->roles()->first()?->revokePermissionTo('tasks.manage');
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    board($project)->assertActionHidden('createTask');
});

it('attaches an uploaded file to a task edited from the board', function () {
    Storage::fake('local');

    $tenant = Tenant::factory()->create();
    $project = Project::factory()->create(['tenant_id' => $tenant->id]);
    $task = Task::factory()->forProject($project)->create();

    // Stored the way the upload field names files: a random prefix, the
    // separator, then the name the person actually chose.
    $path = UploadedFile::fake()->image('mockup.png')
        ->storeAs('task-attachments/'.$tenant->id, 'abcdef__mockup.png', 'local');

    actingInTenant(User::factory()->for($tenant)->admin()->create());

    BoardTaskForm::storeAttachments($task, [$path]);

    $attachment = $task->fresh()->attachments()->first();

    expect($attachment)->not->toBeNull()
        // The original name rides along inside the stored filename, because the
        // upload and the save happen in different requests.
        ->and($attachment->original_name)->toBe('mockup.png')
        ->and($attachment->size_bytes)->toBeGreaterThan(0)
        ->and($attachment->isImage())->toBeTrue();
});

it('ignores an upload path that is not on disk', function () {
    Storage::fake('local');

    $tenant = Tenant::factory()->create();
    $task = Task::factory()->forProject(Project::factory()->create(['tenant_id' => $tenant->id]))->create();

    BoardTaskForm::storeAttachments($task, ['task-attachments/1/does-not-exist.png', '']);

    expect($task->attachments()->count())->toBe(0);
});
