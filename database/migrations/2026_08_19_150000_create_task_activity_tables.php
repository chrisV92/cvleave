<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 4 of the Task Manager: time tracking, attachments and comments.
 *
 * The first two were originally asked for as custom field types. Neither can
 * be one — a timer is a log of sessions rather than a single value, and a file
 * needs an access check — so both get their own tables.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Not every board wants to be a timesheet or a file store, so each is
        // opt-in per project.
        Schema::table('projects', function (Blueprint $table) {
            $table->boolean('time_tracking_enabled')->default(false)->after('color');
            $table->boolean('attachments_enabled')->default(true)->after('time_tracking_enabled');
        });

        // One row per stretch of work. A null ended_at is a running timer,
        // which is what makes "who is working on what right now" answerable.
        Schema::create('task_time_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->string('note')->nullable();
            $table->timestamps();

            $table->index(['task_id', 'started_at']);
            // Finding a person's running timer is the hottest lookup here:
            // every start has to stop whatever they had going before.
            $table->index(['user_id', 'ended_at']);
        });

        Schema::create('task_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();

            // The disk is recorded rather than assumed, so moving to S3 later
            // does not orphan everything already stored locally.
            $table->string('disk');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->timestamps();

            $table->index('task_id');
        });

        Schema::create('task_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('body');
            $table->timestamps();

            $table->index(['task_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_comments');
        Schema::dropIfExists('task_attachments');
        Schema::dropIfExists('task_time_entries');

        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['time_tracking_enabled', 'attachments_enabled']);
        });
    }
};
