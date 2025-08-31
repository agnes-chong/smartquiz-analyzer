<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Teacher
        User::firstOrCreate(
            ['email' => 't@t.com'],
            [
                'name' => 'Teacher',
                'password' => Hash::make('pass'),
                'role' => 'teacher',   // if you have a role column
            ]
        );

        // Student
        User::firstOrCreate(
            ['email' => 's1@s.com'],
            [
                'name' => 'Student',
                'password' => Hash::make('pass'),
                'role' => 'student',   // if you have a role column
            ]
        );
    }
}
