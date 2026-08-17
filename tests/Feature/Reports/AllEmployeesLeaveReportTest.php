<?php

use App\Models\User;

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
