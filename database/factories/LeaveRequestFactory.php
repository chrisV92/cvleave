<?php

namespace Database\Factories;

use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class LeaveRequestFactory extends Factory
{
    public function definition(): array
    {
        $start = fake()->dateTimeBetween('+1 week', '+2 months');
        $end = (clone $start)->modify('+3 days');

        return [
            'user_id' => User::factory(),
            'leave_type_id' => LeaveType::factory(),
            'start_date' => $start,
            'end_date' => $end,
            'duration_type' => LeaveRequest::DURATION_FULL_DAY,
            'days_count' => 4,
            'status' => LeaveRequest::STATUS_PENDING,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LeaveRequest::STATUS_APPROVED,
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LeaveRequest::STATUS_REJECTED,
            'rejection_reason' => fake()->sentence(),
        ]);
    }

    public function halfDay(): static
    {
        $day = fake()->dateTimeBetween('+1 week', '+2 months');

        return $this->state(fn (array $attributes) => [
            'start_date' => $day,
            'end_date' => $day,
            'duration_type' => LeaveRequest::DURATION_HALF_DAY,
            'hours' => null,
            'days_count' => 0.5,
        ]);
    }

    public function hours(float $hours = 2): static
    {
        $day = fake()->dateTimeBetween('+1 week', '+2 months');

        return $this->state(fn (array $attributes) => [
            'start_date' => $day,
            'end_date' => $day,
            'duration_type' => LeaveRequest::DURATION_HOURS,
            'hours' => $hours,
            'days_count' => round($hours / 8, 3),
        ]);
    }
}
