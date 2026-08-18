<?php

use App\Models\Tenant;
use App\Models\User;

it('lets an employee download their own leave report', function () {
    $employee = User::factory()->create();

    $response = $this->actingAs($employee)->get(route('reports.employee-leave', $employee));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toBe('application/pdf');
});

it('forbids an employee from downloading someone else\'s leave report', function () {
    $employee = User::factory()->create();
    $otherEmployee = User::factory()->create();

    $response = $this->actingAs($employee)->get(route('reports.employee-leave', $otherEmployee));

    $response->assertForbidden();
});

it('lets an admin download an employee\'s leave report within their own tenant', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->for($tenant)->admin()->create();
    $employee = User::factory()->for($tenant)->create();

    $response = $this->actingAs($admin)->get(route('reports.employee-leave', $employee));

    $response->assertOk();
});

it('forbids an admin from downloading another tenant\'s employee leave report', function () {
    $admin = User::factory()->admin()->create();
    $otherTenantEmployee = User::factory()->create();

    $response = $this->actingAs($admin)->get(route('reports.employee-leave', $otherTenantEmployee));

    $response->assertForbidden();
});

it('redirects a guest trying to download a leave report to the login page', function () {
    $employee = User::factory()->create();

    $response = $this->get(route('reports.employee-leave', $employee));

    $response->assertRedirect();
});
