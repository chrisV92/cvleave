<?php

use App\Services\LeaveBalanceService;

beforeEach(function () {
    $this->service = new LeaveBalanceService;
});

it('returns 0 when the user has no hire date', function () {
    $user = unpersistedUser(null);

    expect($this->service->greekLawEntitledDays($user, now()))->toBe(0.0);
});

it('returns 0 when asOf is before the hire date', function () {
    $user = unpersistedUser(now()->addYear()->toDateString());

    expect($this->service->greekLawEntitledDays($user, now()))->toBe(0.0);
});

it('prorates 2 days per completed month during the first year, capped at 20', function () {
    $asOf = now();

    expect($this->service->greekLawEntitledDays(unpersistedUser($asOf->copy()->subMonths(1)->toDateString()), $asOf))->toBe(2.0)
        ->and($this->service->greekLawEntitledDays(unpersistedUser($asOf->copy()->subMonths(5)->toDateString()), $asOf))->toBe(10.0)
        ->and($this->service->greekLawEntitledDays(unpersistedUser($asOf->copy()->subMonths(11)->toDateString()), $asOf))->toBe(20.0)
        ->and($this->service->greekLawEntitledDays(unpersistedUser($asOf->copy()->subDays(2)->toDateString()), $asOf))->toBe(0.0);
});

it('grants 21 days in the second employment year', function () {
    $asOf = now();
    $user = unpersistedUser($asOf->copy()->subMonths(18)->toDateString());

    expect($this->service->greekLawEntitledDays($user, $asOf))->toBe(21.0);
});

it('grants 22 days from the third employment year, under the 12/10-year thresholds', function () {
    $asOf = now();
    $user = unpersistedUser($asOf->copy()->subYears(3)->toDateString());

    expect($this->service->greekLawEntitledDays($user, $asOf))->toBe(22.0);
});

it('does not grant 25 days when total career seniority is under 12 and employer tenure is under 10', function () {
    $asOf = now();
    // 3 years here + 7 prior = 10 total, still under both thresholds.
    $user = unpersistedUser($asOf->copy()->subYears(3)->toDateString(), priorYears: 7);

    expect($this->service->greekLawEntitledDays($user, $asOf))->toBe(22.0);
});

it('grants 25 days once total career seniority reaches 12 years, even at a new employer', function () {
    $asOf = now();
    // 3 years here + 9 prior = 12 total.
    $user = unpersistedUser($asOf->copy()->subYears(3)->toDateString(), priorYears: 9);

    expect($this->service->greekLawEntitledDays($user, $asOf))->toBe(25.0);
});

it('grants 25 days once employer tenure alone reaches 10 years, regardless of prior experience', function () {
    $asOf = now();
    $user = unpersistedUser($asOf->copy()->subYears(10)->toDateString(), priorYears: 0);

    expect($this->service->greekLawEntitledDays($user, $asOf))->toBe(25.0);
});

it('grants 26 days once total career seniority reaches 25 years', function () {
    $asOf = now();
    // 3 years here + 22 prior = 25 total.
    $user = unpersistedUser($asOf->copy()->subYears(3)->toDateString(), priorYears: 22);

    expect($this->service->greekLawEntitledDays($user, $asOf))->toBe(26.0);
});

it('handles the exact 12-year boundary as inclusive', function () {
    $asOf = now();
    $user = unpersistedUser($asOf->copy()->subYears(3)->toDateString(), priorYears: 9.0);

    // total = 12.0 exactly -> should already be 25, not still 22.
    expect($this->service->greekLawEntitledDays($user, $asOf))->toBe(25.0);
});
