<?php

use App\Filament\Resources\LeaveTypes\RelationManagers\AccrualRulesRelationManager;
use App\Models\LeaveType;

it('hides the accrual rules tab when the Greek law formula is enabled', function () {
    $leaveType = LeaveType::factory()->greekLaw()->create();

    expect(AccrualRulesRelationManager::canViewForRecord($leaveType, 'edit'))->toBeFalse();
});

it('shows the accrual rules tab when using custom tiers', function () {
    $leaveType = LeaveType::factory()->create(['use_greek_law_formula' => false]);

    expect(AccrualRulesRelationManager::canViewForRecord($leaveType, 'edit'))->toBeTrue();
});
