<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 2 of the Task Manager: fields each company defines for itself.
 */
return new class extends Migration
{
    public function up(): void
    {
        // A definition with no project_id belongs to the whole company and
        // applies to every board. A project can add its own on top.
        //
        // Note this differs from task_statuses, which projects *copy*: columns
        // are the shape of one board's workflow, so each needs its own, while
        // a field like "Contract value" means the same thing everywhere and is
        // better defined once than duplicated per project.
        Schema::create('custom_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('key');
            $table->string('name');
            $table->string('type');
            $table->json('options')->nullable();
            $table->string('help_text')->nullable();
            $table->boolean('is_required')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->unique(['tenant_id', 'project_id', 'key']);
            $table->index(['tenant_id', 'project_id', 'position']);
        });

        Schema::create('custom_field_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('custom_field_id')->constrained()->cascadeOnDelete();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();

            // One typed column per storage class, so filtering and sorting stay
            // indexable. 255 rather than something longer because InnoDB caps
            // an index key at 3072 bytes and utf8mb4 costs 4 per character —
            // longer prose belongs in value_text, which is not indexed.
            $table->string('value_string', 255)->nullable();
            $table->text('value_text')->nullable();
            $table->decimal('value_number', 20, 4)->nullable();
            $table->date('value_date')->nullable();
            $table->boolean('value_boolean')->nullable();
            $table->json('value_json')->nullable();

            $table->timestamps();

            $table->unique(['custom_field_id', 'task_id']);
            $table->index(['custom_field_id', 'value_string']);
            $table->index(['custom_field_id', 'value_number']);
            $table->index(['custom_field_id', 'value_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_field_values');
        Schema::dropIfExists('custom_fields');
    }
};
