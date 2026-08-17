<?php

namespace Database\Factories;

use App\Models\LeaveType;
use Illuminate\Database\Eloquent\Factories\Factory;

class LeaveAccrualRuleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'leave_type_id' => LeaveType::factory(),
            'min_years_service' => 0,
            'max_years_service' => null,
            'days_per_year' => 20,
        ];
    }
}
