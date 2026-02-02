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
        Schema::create('venue_intervals', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('venue_id');
            $table->enum('day', ['sunday','monday','tuesday','wednesday','thursday','friday','saturday']);
            $table->time('start_time');
            $table->time('end_time');
            $table->decimal('price', 10, 2);
            $table->decimal('slot_duration', 5, 2)->default(1.5); // in hours
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('venue_intervals');
    }
};
