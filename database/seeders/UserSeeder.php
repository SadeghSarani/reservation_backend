<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['name' => 'مدیر سیستم', 'email' => 'admin@reservation.test', 'role' => 'super_admin', 'password' => 'Admin@123456'],
            ['name' => 'مدیر مجموعه آزادی', 'email' => 'venue@reservation.test', 'role' => 'venue_admin', 'password' => 'Venue@123456'],
            ['name' => 'مربی تنیس', 'email' => 'coach@reservation.test', 'role' => 'instructor', 'password' => 'Coach@123456'],
            ['name' => 'مربی بدنسازی', 'email' => 'fitness@reservation.test', 'role' => 'instructor', 'password' => 'Coach@123456'],
            ['name' => 'کاربر نمونه', 'email' => 'user@reservation.test', 'role' => 'user', 'password' => 'User@123456'],
        ];

        foreach ($users as $data) {
            User::updateOrCreate(
                ['email' => $data['email']],
                ['name' => $data['name'], 'role' => $data['role'], 'password' => Hash::make($data['password'])],
            );
        }
    }
}
