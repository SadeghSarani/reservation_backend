<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_upgrade_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('requested_role')->default('venue_admin');
            $table->string('business_name')->nullable();
            $table->string('phone', 30);
            $table->text('reason')->nullable();
            $table->string('status')->default('pending')->index();
            $table->boolean('pending_marker')->nullable();
            $table->text('admin_note')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable()->index();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'pending_marker']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_upgrade_requests');
    }
};
