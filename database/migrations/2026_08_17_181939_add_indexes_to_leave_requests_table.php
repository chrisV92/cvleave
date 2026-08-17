<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            // Used by usedDays()/hasOverlap(): filtering a user's requests by leave type + status.
            $table->index(['user_id', 'leave_type_id', 'status']);
            // Used by SendLeaveReminders: scanning ALL users' requests by status + date, unscoped by user.
            $table->index(['status', 'start_date']);
            $table->index(['status', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'leave_type_id', 'status']);
            $table->dropIndex(['status', 'start_date']);
            $table->dropIndex(['status', 'end_date']);
        });
    }
};
