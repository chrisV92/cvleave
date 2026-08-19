<?php

use App\Models\Project;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\TaskComment;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Permissions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\PermissionRegistrar;

function attachTo(Task $task, User $uploader, string $name = 'σχεδιο.png'): TaskAttachment
{
    $file = UploadedFile::fake()->image($name);
    $path = $file->store('task-attachments/'.$task->tenant_id, 'local');

    return TaskAttachment::create([
        'task_id' => $task->id,
        'uploaded_by' => $uploader->id,
        'disk' => 'local',
        'path' => $path,
        'original_name' => $name,
        'mime_type' => 'image/png',
        'size_bytes' => 2048,
    ]);
}

beforeEach(function () {
    Storage::fake('local');
});

it('serves an attachment to someone in the same company', function () {
    $tenant = Tenant::factory()->create();
    $project = Project::factory()->create(['tenant_id' => $tenant->id]);
    $task = Task::factory()->forProject($project)->create();
    $user = User::factory()->for($tenant)->create();

    $attachment = attachTo($task, $user);

    $this->actingAs($user)
        ->get(route('task-attachments.show', $attachment))
        ->assertOk();
});

it('refuses an attachment belonging to another company', function () {
    $acme = Tenant::factory()->create();
    $other = Tenant::factory()->create();

    $theirTask = Task::factory()->forProject(Project::factory()->create(['tenant_id' => $other->id]))->create();
    $theirUser = User::factory()->for($other)->create();
    $attachment = attachTo($theirTask, $theirUser);

    // This is the whole reason the files are not on the public disk: holding
    // the URL must not be enough.
    $this->actingAs(User::factory()->for($acme)->create())
        ->get(route('task-attachments.show', $attachment))
        ->assertForbidden();
});

it('refuses anyone not signed in', function () {
    $tenant = Tenant::factory()->create();
    $task = Task::factory()->forProject(Project::factory()->create(['tenant_id' => $tenant->id]))->create();
    $attachment = attachTo($task, User::factory()->for($tenant)->create());

    $this->get(route('task-attachments.show', $attachment))
        ->assertRedirect();
});

it('refuses somebody whose role cannot see tasks at all', function () {
    $tenant = Tenant::factory()->create();
    $task = Task::factory()->forProject(Project::factory()->create(['tenant_id' => $tenant->id]))->create();
    $user = User::factory()->for($tenant)->create();
    $attachment = attachTo($task, $user);

    app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
    $user->roles()->first()?->revokePermissionTo(Permissions::TASKS_VIEW);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->actingAs($user->fresh())
        ->get(route('task-attachments.show', $attachment))
        ->assertForbidden();
});

it('reports a missing file rather than serving an empty response', function () {
    $tenant = Tenant::factory()->create();
    $task = Task::factory()->forProject(Project::factory()->create(['tenant_id' => $tenant->id]))->create();
    $user = User::factory()->for($tenant)->create();
    $attachment = attachTo($task, $user);

    Storage::disk('local')->delete($attachment->path);

    $this->actingAs($user)
        ->get(route('task-attachments.show', $attachment))
        ->assertNotFound();
});

it('deletes the stored file when the attachment row goes', function () {
    $tenant = Tenant::factory()->create();
    $task = Task::factory()->forProject(Project::factory()->create(['tenant_id' => $tenant->id]))->create();
    $attachment = attachTo($task, User::factory()->for($tenant)->create());

    Storage::disk('local')->assertExists($attachment->path);

    $attachment->delete();

    // Otherwise deleting a task leaves its files on disk forever.
    Storage::disk('local')->assertMissing($attachment->path);
});

it('takes attachments and comments with the task', function () {
    $tenant = Tenant::factory()->create();
    $task = Task::factory()->forProject(Project::factory()->create(['tenant_id' => $tenant->id]))->create();
    $user = User::factory()->for($tenant)->create();

    attachTo($task, $user);
    $task->comments()->create(['user_id' => $user->id, 'body' => 'Ένα σχόλιο']);

    $task->delete();

    expect(TaskAttachment::where('task_id', $task->id)->count())->toBe(0)
        ->and(TaskComment::where('task_id', $task->id)->count())->toBe(0);
});
