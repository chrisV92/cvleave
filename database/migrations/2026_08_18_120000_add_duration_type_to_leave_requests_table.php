<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->string('duration_type')->default('full_day')->after('end_date');
            $table->decimal('hours', 4, 2)->nullable()->after('duration_type');
            $table->decimal('days_count', 5, 3)->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropColumn(['duration_type', 'hours']);
            $table->decimal('days_count', 5, 1)->default(0)->change();
        });
    }
};
