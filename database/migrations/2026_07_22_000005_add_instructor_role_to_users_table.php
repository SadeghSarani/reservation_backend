<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY role ENUM('user','venue_admin','instructor','super_admin') NOT NULL DEFAULT 'user'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::table('users')->where('role', 'instructor')->update(['role' => 'user']);
            DB::statement("ALTER TABLE users MODIFY role ENUM('user','venue_admin','super_admin') NOT NULL DEFAULT 'user'");
        }
    }
};
