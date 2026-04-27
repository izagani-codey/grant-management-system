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

        // Remove old admission user if exists
        User::where('email', 'admissions@example.com')->delete();

        // Seed Updated Admissions Dev Account with complete profile
        User::updateOrCreate(
            ['email' => 'admissions@example.com'],
            [
                'name' => 'Admissions Dev User',
                'password' => Hash::make('password'),
                'role' => 'admission',
                'email_verified_at' => now(),
                // Complete staff profile
                'staff_id' => 'DEV001',
                'designation' => 'Admissions Officer',
                'department' => 'Student Affairs',
                'phone' => '+60123456789',
                'employee_level' => 'Executive',
            ]
        );

        // Staff 1 User
        User::updateOrCreate(
            ['email' => 'staff1@example.com'],
            [
                'name' => 'Staff One', 
                'password' => Hash::make('password'), 
                'role' => 'staff1', 
                'email_verified_at' => now(),
                'staff_id' => 'STF001',
                'designation' => 'Senior Lecturer',
                'department' => 'Academic Affairs',
                'phone' => '+60123456780',
                'employee_level' => 'Senior Executive',
            ]
        );

        // Staff 2 User
        User::updateOrCreate(
            ['email' => 'staff2@example.com'],
            [
                'name' => 'Staff Two',
                'password' => Hash::make('password'),
                'role' => 'staff2',
                'email_verified_at' => now(),
                'staff_id' => 'STF002',
                'designation' => 'Director',
                'department' => 'Academic Affairs',
                'phone' => '+60123456781',
                'employee_level' => 'Management',
            ]
        );

        // Admin User
        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'System Administrator', 
                'password' => Hash::make('password'), 
                'role' => 'admin', 
                'email_verified_at' => now(),
                'staff_id' => 'ADM001',
                'designation' => 'System Administrator',
                'department' => 'IT Department',
                'phone' => '+60123456783',
                'employee_level' => 'Management',
            ]
        );

        // Create templates after users and request types are created
        $this->call([
            TemplateSeeder::class,
        ]);

        $this->command->info('Seeded updated development accounts:');
        $this->command->info('   admissions@example.com (password) - Admissions Dev User');
        $this->command->info('   staff1@example.com (password) - Staff One');
        $this->command->info('   staff2@example.com (password) - Staff Two');
        $this->command->info('   admin@example.com (password) - System Administrator');
    }
}