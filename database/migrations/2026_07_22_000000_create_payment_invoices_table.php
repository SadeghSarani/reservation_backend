<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_invoices', function (Blueprint $table) {
            $table->id();
            $table->uuid('number')->unique();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('calendar_interval_id')->nullable()->index();
            $table->unsignedBigInteger('educational_class_id')->nullable()->index();
            $table->unsignedBigInteger('reservation_id')->nullable()->unique();
            $table->unsignedBigInteger('enrollment_id')->nullable()->unique();
            $table->string('purpose')->default('reservation')->index();
            $table->string('gateway')->default('boometo');
            $table->decimal('amount', 12, 2);
            $table->string('status')->default('pending')->index();
            $table->string('reference')->nullable()->index();
            $table->json('reservation_data')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_invoices');
    }
};
