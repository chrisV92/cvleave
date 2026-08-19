<?php

namespace Database\Factories;

use App\Models\CustomField;
use App\Models\Tenant;
use App\Support\CustomFieldType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CustomField>
 */
class CustomFieldFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->word();

        return [
            'tenant_id' => Tenant::factory(),
            'project_id' => null,
            'key' => Str::slug($name, '_'),
            'name' => ucfirst($name),
            'type' => CustomFieldType::Text,
            'options' => null,
            'help_text' => null,
            'is_required' => false,
            'is_active' => true,
            'position' => 0,
        ];
    }

    public function ofType(CustomFieldType $type, array $options = []): static
    {
        return $this->state(fn () => [
            'type' => $type,
            'options' => $options ?: null,
        ]);
    }

    public function required(): static
    {
        return $this->state(fn () => ['is_required' => true]);
    }
}
