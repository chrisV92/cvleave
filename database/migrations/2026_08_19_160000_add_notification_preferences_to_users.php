<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Somewhere to turn the noise down.
 *
 * Only email is switchable. The bell stays on: it lives inside the app, costs
 * nothing to ignore, and is the record of what happened — an inbox that can be
 * silenced is a different thing from a feed that can be scrolled past.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('notify_by_email')->default(true)->after('prior_experience_years');
            $table->boolean('notify_weekly_digest')->default(true)->after('notify_by_email');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['notify_by_email', 'notify_weekly_digest']);
        });
    }
};
