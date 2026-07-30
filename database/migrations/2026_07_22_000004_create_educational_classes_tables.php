<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('educational_classes')) {
            Schema::create('educational_classes', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('instructor_id')->index();
                $table->unsignedBigInteger('venue_id')->nullable()->index();
                $table->string('title');
                $table->string('slug')->unique();
                $table->text('description');
                $table->string('category')->nullable()->index();
                $table->string('level')->default('all');
                $table->unsignedInteger('capacity');
                $table->decimal('price', 12, 2);
                $table->string('location')->nullable();
                $table->json('schedule');
                $table->json('features')->nullable();
                $table->timestamp('registration_deadline')->nullable();
                $table->date('starts_on');
                $table->date('ends_on')->nullable();
                $table->string('status')->default('draft')->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('educational_class_enrollments')) {
            Schema::create('educational_class_enrollments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('educational_class_id')->index();
                $table->unsignedBigInteger('user_id')->index();
                $table->decimal('price', 12, 2);
                $table->string('status')->default('registered')->index();
                $table->string('payment_status')->default('unpaid')->index();
                $table->timestamp('registered_at');
                $table->timestamp('cancelled_at')->nullable();
                $table->timestamps();
                $table->unique(['educational_class_id', 'user_id'], 'edu_class_user_unique');
            });
        } elseif (! collect(Schema::getIndexes('educational_class_enrollments'))->contains('name', 'edu_class_user_unique')) {
            // MySQL DDL can leave the table behind when a later index statement fails.
            Schema::table('educational_class_enrollments', function (Blueprint $table) {
                $table->unique(['educational_class_id', 'user_id'], 'edu_class_user_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('educational_class_enrollments');
        Schema::dropIfExists('educational_classes');
    }
};
