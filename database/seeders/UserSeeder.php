<?php
// database/seeders/UserSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create main admin user
        User::create([
            'name' => 'Akinbolaji Ogungbire',
            'email' => 'akinbolaji.ogungbire@lukefield.com.ng',
            'phone' => '+234 810 814 0870',
            'password' => Hash::make('password'), // Change this password!
            'is_active' => true,
            'two_factor_enabled' => true,
            'email_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
       

        $this->command->info('✅ Users created successfully!');
        $this->command->info('');
        $this->command->info('Login Credentials:');
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->info('Admin User:');
        $this->command->info('  Email: akinbolaji.ogungbire@lukefield.com.ng');
        $this->command->info('  Password: password');
        $this->command->info('');
        
        
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->warn('⚠️  IMPORTANT: Change these passwords immediately after first login!');
    }
}