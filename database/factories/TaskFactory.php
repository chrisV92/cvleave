<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->paragraph(),
            'priority' => fake()->randomElement([
                Task::PRIORITY_LOW,
                Task::PRIORITY_NORMAL,
                Task::PRIORITY_HIGH,
                Task::PRIORITY_URGENT,
            ]),
            'position' => 0,
        ];
    }

    /**
     * Tasks are meaningless without a project, and their tenant and status
     * both have to agree with it — so the factory derives all three rather
     * than letting a caller create an inconsistent row by accident.
     */
    public function forProject(Project $project): static
    {
        return $this->state(fn () => [
            'project_id' => $project->id,
            'tenant_id' => $project->tenant_id,
            'task_status_id' => $project->defaultStatus()?->id,
        ]);
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Task $task) {
            if ($task->project_id) {
                return;
            }

            $project = Project::factory()->create();
            $task->project_id = $project->id;
            $task->tenant_id = $project->tenant_id;
            $task->task_status_id ??= $project->defaultStatus()?->id;
        });
    }
}
