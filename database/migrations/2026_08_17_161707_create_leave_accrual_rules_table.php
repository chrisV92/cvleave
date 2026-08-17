<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_accrual_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('leave_type_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('min_years_service');
            $table->unsignedTinyInteger('max_years_service')->nullable();
            $table->unsignedSmallInteger('days_per_year');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_accrual_rules');
    }
};
