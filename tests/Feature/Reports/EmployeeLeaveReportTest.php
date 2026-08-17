<?php

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

it('lets an admin download any employee\'s leave report', function () {
    $admin = User::factory()->admin()->create();
    $employee = User::factory()->create();

    $response = $this->actingAs($admin)->get(route('reports.employee-leave', $employee));

    $response->assertOk();
});

it('redirects a guest trying to download a leave report to the login page', function () {
    $employee = User::factory()->create();

    $response = $this->get(route('reports.employee-leave', $employee));

    $response->assertRedirect();
});
