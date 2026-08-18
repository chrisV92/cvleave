<?php

it('computes years of service as full completed years since hire_date', function () {
    $user = unpersistedUser(now()->subYears(4)->subMonths(2)->toDateString());

    expect($user->yearsOfService())->toBe(4);
});

it('returns 0 years of service when there is no hire_date', function () {
    $user = unpersistedUser(null);

    expect($user->yearsOfService())->toBe(0);
});

it('sums years of service and prior experience for total career years', function () {
    $user = unpersistedUser(now()->subYears(3)->toDateString(), priorYears: 6.5);

    expect($user->totalCareerYears())->toBe(9.5);
});
