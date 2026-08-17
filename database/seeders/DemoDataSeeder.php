<?php

namespace Database\Seeders;

use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $employees = [
            ['name' => 'Μαρία Παπαδοπούλου', 'email' => 'maria@cvleave.dev.com', 'hire_date' => now()->subMonths(4)],
            ['name' => 'Γιώργος Νικολάου', 'email' => 'giorgos@cvleave.dev.com', 'hire_date' => now()->subYears(5)],
            ['name' => 'Ελένη Κωνσταντίνου', 'email' => 'eleni@cvleave.dev.com', 'hire_date' => now()->subYears(12)],
            ['name' => 'Δημήτρης Αντωνίου', 'email' => 'dimitris@cvleave.dev.com', 'hire_date' => now()->subYears(27)],
        ];

        $users = collect($employees)->map(function ($data) {
            return User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make('password'),
                    'role' => User::ROLE_EMPLOYEE,
                    'hire_date' => $data['hire_date'],
                ]
            );
        });

        $annual = LeaveType::where('name', 'Κανονική Άδεια')->first();
        $sick = LeaveType::where('name', 'Αναρρωτική Άδεια')->first();
        $admin = User::where('role', User::ROLE_ADMIN)->first();

        // Μαρία: pending request starting soon
        LeaveRequest::updateOrCreate(
            ['user_id' => $users[0]->id, 'start_date' => now()->addDays(3)->toDateString()],
            [
                'leave_type_id' => $annual->id,
                'end_date' => now()->addDays(5),
                'days_count' => 3,
                'status' => LeaveRequest::STATUS_PENDING,
            ]
        );

        // Γιώργος: approved, currently ongoing (tests calendar "today")
        LeaveRequest::updateOrCreate(
            ['user_id' => $users[1]->id, 'start_date' => now()->subDays(1)->toDateString()],
            [
                'leave_type_id' => $annual->id,
                'end_date' => now()->addDays(2),
                'days_count' => 4,
                'status' => LeaveRequest::STATUS_APPROVED,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
            ]
        );

        // Ελένη: approved, ending tomorrow (tests the reminder cron)
        LeaveRequest::updateOrCreate(
            ['user_id' => $users[2]->id, 'start_date' => now()->subDays(4)->toDateString()],
            [
                'leave_type_id' => $annual->id,
                'end_date' => now()->addDay(),
                'days_count' => 5,
                'status' => LeaveRequest::STATUS_APPROVED,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
            ]
        );

        // Δημήτρης: rejected sick leave (with note + rejection reason)
        LeaveRequest::updateOrCreate(
            ['user_id' => $users[3]->id, 'start_date' => now()->subDays(10)->toDateString()],
            [
                'leave_type_id' => $sick->id,
                'end_date' => now()->subDays(9),
                'days_count' => 2,
                'status' => LeaveRequest::STATUS_REJECTED,
                'note' => 'Ίωση, ελαφρύ πυρετό.',
                'rejection_reason' => 'Δεν είχε προσκομιστεί ιατρική βεβαίωση.',
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
            ]
        );

        // Δημήτρης: approved leave starting tomorrow (tests the reminder cron)
        LeaveRequest::updateOrCreate(
            ['user_id' => $users[3]->id, 'start_date' => now()->addDay()->toDateString()],
            [
                'leave_type_id' => $annual->id,
                'end_date' => now()->addDays(6),
                'days_count' => 6,
                'status' => LeaveRequest::STATUS_APPROVED,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
            ]
        );

        $this->command->info('Created ' . $users->count() . ' employees with sample leave requests.');
    }
}
