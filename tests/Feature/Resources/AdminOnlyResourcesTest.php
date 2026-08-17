<?php

use App\Filament\Resources\LeaveTypes\LeaveTypeResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;

it('allows an admin to view the Users resource', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    expect(UserResource::canViewAny())->toBeTrue()
        ->and(UserResource::shouldRegisterNavigation())->toBeTrue();
});

it('forbids an employee from viewing the Users resource', function () {
    $employee = User::factory()->create();
    $this->actingAs($employee);

    expect(UserResource::canViewAny())->toBeFalse()
        ->and(UserResource::shouldRegisterNavigation())->toBeFalse();
});

it('allows an admin to view the LeaveTypes resource', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    expect(LeaveTypeResource::canViewAny())->toBeTrue()
        ->and(LeaveTypeResource::shouldRegisterNavigation())->toBeTrue();
});

it('forbids an employee from viewing the LeaveTypes resource', function () {
    $employee = User::factory()->create();
    $this->actingAs($employee);

    expect(LeaveTypeResource::canViewAny())->toBeFalse()
        ->and(LeaveTypeResource::shouldRegisterNavigation())->toBeFalse();
});

it('has no registration route in the admin panel', function () {
    expect(\Illuminate\Support\Facades\Route::has('filament.admin.auth.register'))->toBeFalse();
});

it('redirects a guest hitting the admin panel to the login page', function () {
    $this->get('/admin')->assertRedirect('/admin/login');
});
