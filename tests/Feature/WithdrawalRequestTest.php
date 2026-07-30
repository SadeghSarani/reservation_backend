<?php

namespace Tests\Feature;

use App\Models\EducationalClass;
use App\Models\EducationalClassEnrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WithdrawalRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_instructor_can_request_available_income_and_admin_can_mark_it_paid(): void
    {
        $instructor = $this->user('coach-with-income@example.test', 'instructor');
        $student = $this->user('paid-student@example.test');
        $admin = $this->user('finance-admin@example.test', 'super_admin');
        $class = EducationalClass::create([
            'instructor_id' => $instructor->id, 'title' => 'Paid Class', 'slug' => 'paid-class-test',
            'description' => 'Test', 'level' => 'all', 'capacity' => 10, 'price' => 800000,
            'location' => 'Test location', 'schedule' => [['day' => 'Sat']], 'starts_on' => '2026-09-01',
            'status' => 'published',
        ]);
        EducationalClassEnrollment::create([
            'educational_class_id' => $class->id, 'user_id' => $student->id, 'price' => 800000,
            'status' => 'registered', 'payment_status' => 'paid', 'registered_at' => now(),
        ]);

        $this->actingAs($instructor)->getJson('/api/earnings/balance')
            ->assertOk()->assertJsonPath('available_to_withdraw', 800000);

        $withdrawal = $this->actingAs($instructor)->postJson('/api/withdrawals', [
            'amount' => 500000,
            'iban' => 'IR123456789012345678901234',
            'account_holder' => 'Test Instructor',
        ])->assertCreated()->assertJsonPath('status', 'pending')->json();

        $this->actingAs($instructor)->postJson('/api/withdrawals', [
            'amount' => 400000,
            'iban' => 'IR123456789012345678901234',
            'account_holder' => 'Test Instructor',
        ])->assertUnprocessable();

        $this->actingAs($admin)->patchJson('/api/admin/withdrawals/'.$withdrawal['number'].'/status', [
            'status' => 'approved',
        ])->assertOk();
        $this->actingAs($admin)->patchJson('/api/admin/withdrawals/'.$withdrawal['number'].'/status', [
            'status' => 'paid', 'admin_note' => 'Transferred successfully.',
        ])->assertOk()->assertJsonPath('status', 'paid');
    }

    private function user(string $email, string $role = 'user'): User
    {
        return User::create(['name' => 'Test User', 'email' => $email, 'password' => 'password', 'role' => $role]);
    }
}
