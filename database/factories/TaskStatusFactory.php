<?php

namespace Database\Factories;

use App\Models\TaskStatus;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TaskStatus>
 */
class TaskStatusFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'project_id' => null,
            'name' => fake()->unique()->word(),
            'color' => fake()->hexColor(),
            'position' => 0,
            'is_default' => false,
            'is_completed' => false,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn () => ['is_completed' => true]);
    }
}
