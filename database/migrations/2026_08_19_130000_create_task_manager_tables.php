<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 1 of the Task Manager: projects, the statuses each project moves work
 * through, and the tasks themselves.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('color')->default('#6366f1');
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('archived_at')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->unique(['tenant_id', 'slug']);
            $table->index(['tenant_id', 'archived_at']);
        });

        // A row with a null project_id is one of the company's defaults — the
        // template a new project is created from. Mirrors how leave types are
        // seeded per company, except projects then get their own copy so one
        // can be renamed without disturbing the rest.
        Schema::create('task_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('color')->default('#94a3b8');
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_completed')->default(false);
            $table->timestamps();

            $table->index(['tenant_id', 'project_id', 'position']);
        });

        Schema::create('tasks', function (Blueprint $table) {
            $table->id();

            // Denormalised on purpose. LeaveRequest has no direct tenant link,
            // which forces every query to join through its user; a real
            // relationship lets Filament's own tenant scoping do the work.
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();

            $table->foreignId('project_id')->constrained()->cascadeOnDelete();

            // Restricted rather than cascaded: deleting a status must not take
            // the work in that column with it.
            $table->foreignId('task_status_id')->constrained()->restrictOnDelete();

            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('priority')->nullable();
            $table->date('start_date')->nullable();
            $table->date('due_date')->nullable();

            // Fractional, so a card dropped between two others gets the
            // midpoint instead of renumbering the whole column.
            $table->decimal('position', 20, 10)->default(0);

            $table->timestamp('completed_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'task_status_id', 'position']);
            $table->index(['tenant_id', 'assignee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
        Schema::dropIfExists('task_statuses');
        Schema::dropIfExists('projects');
    }
};
