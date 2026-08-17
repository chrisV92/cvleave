<?php

namespace Database\Seeders;

use App\Models\LeaveType;
use Illuminate\Database\Seeder;

class LeaveTypeSeeder extends Seeder
{
    public function run(): void
    {
        LeaveType::updateOrCreate(
            ['name' => 'Κανονική Άδεια'],
            [
                'color' => '#22c55e',
                'requires_note' => false,
                'auto_calculate' => true,
                'use_greek_law_formula' => true,
                'is_active' => true,
            ]
        );

        LeaveType::updateOrCreate(
            ['name' => 'Αναρρωτική Άδεια'],
            [
                'color' => '#ef4444',
                'requires_note' => true,
                'auto_calculate' => false,
                'fixed_days_per_year' => 0,
                'is_active' => true,
            ]
        );

        LeaveType::updateOrCreate(
            ['name' => 'Άδεια Άνευ Αποδοχών'],
            [
                'color' => '#94a3b8',
                'requires_note' => true,
                'auto_calculate' => false,
                'fixed_days_per_year' => 0,
                'is_active' => true,
            ]
        );
    }
}
