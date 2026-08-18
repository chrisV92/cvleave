<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Per-company cutoff for using the previous year's leftover leave.
        // Both null means this company does not allow carry-over at all.
        Schema::table('tenants', function (Blueprint $table) {
            $table->unsignedTinyInteger('carryover_deadline_month')->nullable()->after('slug');
            $table->unsignedTinyInteger('carryover_deadline_day')->nullable()->after('carryover_deadline_month');
        });

        // Carry-over applies to annual leave, not to sick or unpaid leave.
        Schema::table('leave_types', function (Blueprint $table) {
            $table->boolean('allows_carryover')->default(false)->after('is_active');
        });

        // How much of this request was drawn from the PREVIOUS year's balance.
        // A request can legitimately straddle both years, so this is a portion
        // of days_count rather than a whole-request "which year" flag.
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->decimal('days_from_carryover', 5, 3)->default(0)->after('days_count');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['carryover_deadline_month', 'carryover_deadline_day']);
        });

        Schema::table('leave_types', function (Blueprint $table) {
            $table->dropColumn('allows_carryover');
        });

        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropColumn('days_from_carryover');
        });
    }
};
