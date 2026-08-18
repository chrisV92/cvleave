<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class LeaveTypeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => fake()->unique()->words(2, true),
            'color' => '#22c55e',
            'requires_note' => false,
            'auto_calculate' => false,
            'use_greek_law_formula' => false,
            'fixed_days_per_year' => 20,
            'is_active' => true,
        ];
    }

    public function greekLaw(): static
    {
        return $this->state(fn (array $attributes) => [
            'auto_calculate' => true,
            'use_greek_law_formula' => true,
            'fixed_days_per_year' => null,
        ]);
    }

    public function requiresNote(): static
    {
        return $this->state(fn (array $attributes) => [
            'requires_note' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
