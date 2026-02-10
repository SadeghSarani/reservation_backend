<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('venues', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('owner_id');
            $table->string('name');
            $table->enum('type', ['gym', 'tennis', 'football', 'basketball', 'futsal']);
            $table->text('description')->nullable();
            $table->string('address')->nullable();
            $table->integer('capacity')->nullable();
            $table->enum('billing_type', ['hourly', 'monthly']);
            $table->boolean('is_active');
            $table->decimal('price', 10, 2);
            $table->json('additionals')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('venues');
    }
};
