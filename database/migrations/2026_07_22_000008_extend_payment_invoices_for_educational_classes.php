<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_invoices', function (Blueprint $table) {
            if (! Schema::hasColumn('payment_invoices', 'educational_class_id')) {
                $table->unsignedBigInteger('educational_class_id')->nullable()->index();
            }
            if (! Schema::hasColumn('payment_invoices', 'enrollment_id')) {
                $table->unsignedBigInteger('enrollment_id')->nullable()->unique();
            }
            if (! Schema::hasColumn('payment_invoices', 'purpose')) {
                $table->string('purpose')->default('reservation')->index();
            }
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE payment_invoices MODIFY calendar_interval_id BIGINT UNSIGNED NULL');
            DB::statement('ALTER TABLE payment_invoices MODIFY reservation_data JSON NULL');
        }
    }

    public function down(): void
    {
        Schema::table('payment_invoices', function (Blueprint $table) {
            foreach (['educational_class_id', 'enrollment_id', 'purpose'] as $column) {
                if (Schema::hasColumn('payment_invoices', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
