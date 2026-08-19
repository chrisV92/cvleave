<?php

use App\Filament\Resources\CustomFields\CustomFieldResource;
use App\Models\CustomField;
use App\Models\Project;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\User;
use App\Support\CustomFieldType;

it('stores each field type in its own typed column', function () {
    $tenant = Tenant::factory()->create();
    $project = Project::factory()->create(['tenant_id' => $tenant->id]);
    $task = Task::factory()->forProject($project)->create();
    $user = User::factory()->for($tenant)->create();

    $cases = [
        [CustomFieldType::Text, 'Ένα κείμενο', 'value_string'],
        [CustomFieldType::Textarea, str_repeat('μακρύ ', 100), 'value_text'],
        [CustomFieldType::Number, 42.5, 'value_number'],
        [CustomFieldType::Money, 1500.25, 'value_number'],
        [CustomFieldType::Percent, 80, 'value_number'],
        [CustomFieldType::Date, '2026-09-30', 'value_date'],
        [CustomFieldType::Checkbox, true, 'value_boolean'],
        [CustomFieldType::User, $user->id, 'value_number'],
        [CustomFieldType::MultiSelect, ['α', 'β'], 'value_json'],
    ];

    foreach ($cases as [$type, $value, $column]) {
        $field = CustomField::factory()->ofType($type)->create([
            'tenant_id' => $tenant->id,
            'key' => 'f_'.$type->value,
        ]);

        $task->saveCustomFieldState([$field->id => $value]);

        $row = $task->customFieldValues()->where('custom_field_id', $field->id)->first();

        expect($row->{$column})->not->toBeNull("{$type->value} should land in {$column}");

        // Every other column has to stay empty, or a type change would leave
        // stale values behind in columns nothing reads.
        foreach (['value_string', 'value_text', 'value_number', 'value_date', 'value_boolean', 'value_json'] as $other) {
            if ($other !== $column) {
                expect($row->{$other})->toBeNull("{$type->value} left something in {$other}");
            }
        }
    }
});

it('reads values back in the type they were written as', function () {
    $tenant = Tenant::factory()->create();
    $project = Project::factory()->create(['tenant_id' => $tenant->id]);
    $task = Task::factory()->forProject($project)->create();

    $number = CustomField::factory()->ofType(CustomFieldType::Number)->create(['tenant_id' => $tenant->id, 'key' => 'n']);
    $flag = CustomField::factory()->ofType(CustomFieldType::Checkbox)->create(['tenant_id' => $tenant->id, 'key' => 'b']);
    $tags = CustomField::factory()->ofType(CustomFieldType::MultiSelect)->create(['tenant_id' => $tenant->id, 'key' => 'm']);

    $task->saveCustomFieldState([
        $number->id => 12.75,
        $flag->id => true,
        $tags->id => ['ένα', 'δύο'],
    ]);

    $state = $task->fresh()->customFieldState();

    expect($state[$number->id])->toBe(12.75)
        ->and($state[$flag->id])->toBeTrue()
        ->and($state[$tags->id])->toBe(['ένα', 'δύο']);
});

it('clears a value when the answer is emptied rather than storing a blank row', function () {
    $tenant = Tenant::factory()->create();
    $project = Project::factory()->create(['tenant_id' => $tenant->id]);
    $task = Task::factory()->forProject($project)->create();
    $field = CustomField::factory()->create(['tenant_id' => $tenant->id]);

    $task->saveCustomFieldState([$field->id => 'κάτι']);
    expect($task->customFieldValues()->count())->toBe(1);

    $task->saveCustomFieldState([$field->id => '']);
    expect($task->fresh()->customFieldValues()->count())->toBe(0);
});

it('ignores a field that does not apply to the task\'s project', function () {
    $tenant = Tenant::factory()->create();
    $project = Project::factory()->create(['tenant_id' => $tenant->id]);
    $elsewhere = Project::factory()->create(['tenant_id' => $tenant->id]);
    $task = Task::factory()->forProject($project)->create();

    $foreign = CustomField::factory()->create([
        'tenant_id' => $tenant->id,
        'project_id' => $elsewhere->id,
    ]);

    // A crafted submission naming another board's field must not attach it.
    $task->saveCustomFieldState([$foreign->id => 'δεν πρέπει']);

    expect($task->customFieldValues()->count())->toBe(0);
});

it('ignores a field belonging to another company', function () {
    $acme = Tenant::factory()->create();
    $other = Tenant::factory()->create();

    $task = Task::factory()->forProject(Project::factory()->create(['tenant_id' => $acme->id]))->create();
    $theirs = CustomField::factory()->create(['tenant_id' => $other->id]);

    $task->saveCustomFieldState([$theirs->id => 'διαρροή']);

    expect($task->customFieldValues()->count())->toBe(0);
});

it('applies company-wide fields to every project and project fields to just one', function () {
    $tenant = Tenant::factory()->create();
    $sales = Project::factory()->create(['tenant_id' => $tenant->id]);
    $build = Project::factory()->create(['tenant_id' => $tenant->id]);

    $companyWide = CustomField::factory()->create(['tenant_id' => $tenant->id, 'key' => 'everywhere']);
    $salesOnly = CustomField::factory()->create([
        'tenant_id' => $tenant->id,
        'project_id' => $sales->id,
        'key' => 'sales_only',
    ]);

    expect(CustomField::forProject($sales)->pluck('id')->all())
        ->toContain($companyWide->id, $salesOnly->id)
        ->and(CustomField::forProject($build)->pluck('id')->all())
        ->toContain($companyWide->id)
        ->not->toContain($salesOnly->id);
});

it('takes its values with it when a field definition is deleted', function () {
    $tenant = Tenant::factory()->create();
    $project = Project::factory()->create(['tenant_id' => $tenant->id]);
    $task = Task::factory()->forProject($project)->create();
    $field = CustomField::factory()->create(['tenant_id' => $tenant->id]);

    $task->saveCustomFieldState([$field->id => 'τιμή']);
    $field->delete();

    expect($task->fresh()->customFieldValues()->count())->toBe(0);
});

it('leaves inactive fields out of a project\'s set', function () {
    $tenant = Tenant::factory()->create();
    $project = Project::factory()->create(['tenant_id' => $tenant->id]);

    $live = CustomField::factory()->create(['tenant_id' => $tenant->id, 'key' => 'live']);
    $retired = CustomField::factory()->create(['tenant_id' => $tenant->id, 'key' => 'retired', 'is_active' => false]);

    expect(CustomField::forProject($project)->pluck('id')->all())
        ->toContain($live->id)
        ->not->toContain($retired->id);
});

it('keeps the company field list free of any single project\'s fields', function () {
    $tenant = Tenant::factory()->create();
    $project = Project::factory()->create(['tenant_id' => $tenant->id]);

    $companyWide = CustomField::factory()->create(['tenant_id' => $tenant->id, 'key' => 'shared']);
    $projectOnly = CustomField::factory()->create([
        'tenant_id' => $tenant->id,
        'project_id' => $project->id,
        'key' => 'board_only',
    ]);

    actingInTenant(User::factory()->for($tenant)->admin()->create());

    $listed = CustomFieldResource::getEloquentQuery()->pluck('id')->all();

    expect($listed)->toContain($companyWide->id)->not->toContain($projectOnly->id);
});

it('keeps one company\'s fields out of another\'s list', function () {
    $acme = Tenant::factory()->create();
    $other = Tenant::factory()->create();

    $mine = CustomField::factory()->create(['tenant_id' => $acme->id, 'key' => 'mine']);
    $theirs = CustomField::factory()->create(['tenant_id' => $other->id, 'key' => 'theirs']);

    actingInTenant(User::factory()->for($acme)->admin()->create());

    $listed = CustomFieldResource::getEloquentQuery()->pluck('id')->all();

    expect($listed)->toContain($mine->id)->not->toContain($theirs->id);
});
