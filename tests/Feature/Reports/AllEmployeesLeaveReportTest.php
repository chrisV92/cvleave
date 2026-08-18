<?php

use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Tenant;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;

it('lets an admin download the all-employees leave report', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get(route('reports.all-employees-leave'));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toBe('application/pdf');
});

it('forbids an employee from downloading the all-employees leave report', function () {
    $employee = User::factory()->create();

    $response = $this->actingAs($employee)->get(route('reports.all-employees-leave'));

    $response->assertForbidden();
});

it('redirects a guest trying to download the all-employees leave report to the login page', function () {
    $response = $this->get(route('reports.all-employees-leave'));

    $response->assertRedirect();
});

it('only includes leave requests from the admin\'s own tenant', function () {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();
    $admin = User::factory()->for($tenantA)->admin()->create();
    $employeeA = User::factory()->for($tenantA)->create();
    $employeeB = User::factory()->for($tenantB)->create();
    $typeA = LeaveType::factory()->for($tenantA)->create();
    $typeB = LeaveType::factory()->for($tenantB)->create();

    LeaveRequest::factory()->for($employeeA)->for($typeA)->approved()->create();
    LeaveRequest::factory()->for($employeeB)->for($typeB)->approved()->create();

    $capturedData = null;
    Pdf::shouldReceive('loadView')
        ->once()
        ->andReturnUsing(function ($view, $data) use (&$capturedData) {
            $capturedData = $data;
            $mock = Mockery::mock();
            $mock->shouldReceive('setPaper')->andReturnSelf();
            $mock->shouldReceive('download')->andReturn(response('fake'));

            return $mock;
        });

    $this->actingAs($admin)->get(route('reports.all-employees-leave'));

    $userIds = collect($capturedData['leaveRequests'])->pluck('user_id')->all();

    expect($userIds)->toContain($employeeA->id)
        ->and($userIds)->not->toContain($employeeB->id);
});
