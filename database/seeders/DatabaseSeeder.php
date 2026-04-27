<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            VotCodeSeeder::class,
            RequestTypeSeeder::class,
        ]);

        $devPassword = env('DEV_SEED_PASSWORD');

        if (app()->environment(['local', 'testing']) && $devPassword) {
            // Remove old admission user if exists
            User::where('email', 'admissions@example.com')->delete();

            User::updateOrCreate(
                ['email' => 'admissions@example.com'],
                [
                    'name' => 'Admissions Dev User',
                    'password' => Hash::make($devPassword),
                    'role' => 'admission',
                    'email_verified_at' => now(),
                    'staff_id' => 'DEV001',
                    'designation' => 'Admissions Officer',
                    'department' => 'Student Affairs',
                    'phone' => '+60123456789',
                    'employee_level' => 'Executive',
                ]
            );

            User::updateOrCreate(
                ['email' => 'staff1@example.com'],
                [
                    'name' => 'Staff One',
                    'password' => Hash::make($devPassword),
                    'role' => 'staff1',
                    'email_verified_at' => now(),
                    'staff_id' => 'STF001',
                    'designation' => 'Senior Lecturer',
                    'department' => 'Academic Affairs',
                    'phone' => '+60123456780',
                    'employee_level' => 'Senior Executive',
                ]
            );

            User::updateOrCreate(
                ['email' => 'staff2@example.com'],
                [
                    'name' => 'Staff Two',
                    'password' => Hash::make($devPassword),
                    'role' => 'staff2',
                    'email_verified_at' => now(),
                    'staff_id' => 'STF002',
                    'designation' => 'Director',
                    'department' => 'Academic Affairs',
                    'phone' => '+60123456781',
                    'employee_level' => 'Management',
                ]
            );

            User::updateOrCreate(
                ['email' => 'admin@example.com'],
                [
                    'name' => 'System Administrator',
                    'password' => Hash::make($devPassword),
                    'role' => 'admin',
                    'email_verified_at' => now(),
                    'staff_id' => 'ADM001',
                    'designation' => 'System Administrator',
                    'department' => 'IT Department',
                    'phone' => '+60123456783',
                    'employee_level' => 'Management',
                ]
            );

            $this->command->info('Seeded development accounts from DEV_SEED_PASSWORD.');
        } else {
            $this->command->info('Skipped development account seeding.');
        }

        // Create templates after users and request types are created
        $this->call([
            TemplateSeeder::class,
        ]);

        $this->command->info('Seeded request types and templates.');
    }
}
