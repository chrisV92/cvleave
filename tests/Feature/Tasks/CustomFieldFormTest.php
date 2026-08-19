<?php

use App\Filament\Resources\Tasks\Pages\CreateTask;
use App\Filament\Resources\Tasks\Pages\EditTask;
use App\Models\CustomField;
use App\Models\Project;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\User;
use App\Services\CustomFieldSchema;
use App\Support\CustomFieldType;
use Livewire\Livewire;

function fieldPath(CustomField $field): string
{
    return CustomFieldSchema::STATE_KEY.'.'.$field->id;
}

it('saves custom field answers alongside a new task', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->for($tenant)->admin()->create();
    $project = Project::factory()->create(['tenant_id' => $tenant->id]);

    $budget = CustomField::factory()->ofType(CustomFieldType::Money)->create([
        'tenant_id' => $tenant->id, 'name' => 'Προϋπολογισμός', 'key' => 'budget',
    ]);
    $stage = CustomField::factory()
        ->ofType(CustomFieldType::Select, ['Α', 'Β', 'Γ'])
        ->create(['tenant_id' => $tenant->id, 'name' => 'Στάδιο', 'key' => 'stage']);

    actingInTenant($admin);

    Livewire::test(CreateTask::class)
        ->fillForm([
            'title' => 'Με πεδία',
            'project_id' => $project->id,
            'task_status_id' => $project->defaultStatus()->id,
            fieldPath($budget) => 2500,
            fieldPath($stage) => 'Β',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $task = Task::where('title', 'Με πεδία')->first();
    $state = $task->customFieldState();

    expect($state[$budget->id])->toBe(2500.0)
        ->and($state[$stage->id])->toBe('Β');
});

it('fills the form with the answers already on a task', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->for($tenant)->admin()->create();
    $project = Project::factory()->create(['tenant_id' => $tenant->id]);
    $task = Task::factory()->forProject($project)->create();

    $note = CustomField::factory()->create([
        'tenant_id' => $tenant->id, 'name' => 'Σημείωση', 'key' => 'note',
    ]);
    $task->saveCustomFieldState([$note->id => 'προϋπάρχουσα τιμή']);

    actingInTenant($admin);

    Livewire::test(EditTask::class, ['record' => $task->getRouteKey()])
        ->assertFormSet([fieldPath($note) => 'προϋπάρχουσα τιμή']);
});

it('updates an answer without disturbing the others', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->for($tenant)->admin()->create();
    $project = Project::factory()->create(['tenant_id' => $tenant->id]);
    $task = Task::factory()->forProject($project)->create();

    $a = CustomField::factory()->create(['tenant_id' => $tenant->id, 'key' => 'a', 'name' => 'A']);
    $b = CustomField::factory()->create(['tenant_id' => $tenant->id, 'key' => 'b', 'name' => 'B']);
    $task->saveCustomFieldState([$a->id => 'πρώτο', $b->id => 'δεύτερο']);

    actingInTenant($admin);

    // Both are filled because that is what a browser submits — the whole
    // form, not a delta. fillForm() replaces the state it is given rather
    // than merging into it, so passing only one here would blank the other
    // and the test would be measuring the harness, not the code.
    Livewire::test(EditTask::class, ['record' => $task->getRouteKey()])
        ->fillForm([
            fieldPath($a) => 'αλλαγμένο',
            fieldPath($b) => 'δεύτερο',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $state = $task->fresh()->customFieldState();

    expect($state[$a->id])->toBe('αλλαγμένο')
        ->and($state[$b->id])->toBe('δεύτερο');
});

it('enforces a field marked as required', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->for($tenant)->admin()->create();
    $project = Project::factory()->create(['tenant_id' => $tenant->id]);

    $mandatory = CustomField::factory()->required()->create([
        'tenant_id' => $tenant->id, 'name' => 'Κωδικός πελάτη', 'key' => 'client_code',
    ]);

    actingInTenant($admin);

    Livewire::test(CreateTask::class)
        ->fillForm([
            'title' => 'Χωρίς το υποχρεωτικό',
            'project_id' => $project->id,
            'task_status_id' => $project->defaultStatus()->id,
        ])
        ->call('create')
        ->assertHasFormErrors([fieldPath($mandatory)]);
});

it('offers only the fields that belong to the chosen project', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->for($tenant)->admin()->create();
    $sales = Project::factory()->create(['tenant_id' => $tenant->id]);
    $build = Project::factory()->create(['tenant_id' => $tenant->id]);

    $salesOnly = CustomField::factory()->create([
        'tenant_id' => $tenant->id, 'project_id' => $sales->id,
        'name' => 'Αξία συμβολαίου', 'key' => 'contract_value',
    ]);

    actingInTenant($admin);

    Livewire::test(CreateTask::class)
        ->fillForm(['project_id' => $sales->id])
        ->assertFormFieldExists(fieldPath($salesOnly))
        // Switching board has to take that board's fields with it.
        ->fillForm(['project_id' => $build->id])
        ->assertFormFieldDoesNotExist(fieldPath($salesOnly));
});
