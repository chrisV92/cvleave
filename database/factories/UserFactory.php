<?php

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'hire_date' => now()->subYear(),
            'prior_experience_years' => 0,
            'is_platform_admin' => false,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (User $user) {
            app(PermissionRegistrar::class)->setPermissionsTeamId($user->tenant_id);

            if (! $user->hasAnyRole(['admin', 'employee'])) {
                $user->assignRole('employee');
            }
        });
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function admin(): static
    {
        return $this->afterCreating(function (User $user) {
            app(PermissionRegistrar::class)->setPermissionsTeamId($user->tenant_id);
            $user->syncRoles(['admin']);
        });
    }
}
