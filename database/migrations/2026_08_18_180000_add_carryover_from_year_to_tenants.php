<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            // The first year this company has complete leave records for.
            // Carry-over is not offered for anything earlier, so adopting the
            // app does not credit employees with a year nobody ever recorded.
            // Null means "no restriction" (kept permissive for existing rows).
            $table->unsignedSmallInteger('carryover_from_year')
                ->nullable()
                ->after('carryover_deadline_day');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('carryover_from_year');
        });
    }
};
