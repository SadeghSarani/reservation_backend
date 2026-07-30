<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('role_upgrade_requests', 'requested_role')) {
            Schema::table('role_upgrade_requests', function (Blueprint $table) {
                $table->string('requested_role')->default('venue_admin')->after('user_id');
            });
        }

        // The original production table required business_name, while instructor
        // requests legitimately do not have a business name.
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE role_upgrade_requests MODIFY business_name VARCHAR(255) NULL');
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('role_upgrade_requests', 'requested_role')) {
            Schema::table('role_upgrade_requests', function (Blueprint $table) {
                $table->dropColumn('requested_role');
            });
        }
    }
};
