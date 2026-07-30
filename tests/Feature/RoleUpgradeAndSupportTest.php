<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleUpgradeAndSupportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_approve_upgrade_request_and_user_becomes_venue_admin(): void
    {
        $user = $this->user('user@example.test');
        $admin = $this->user('admin@example.test', 'super_admin');

        $request = $this->actingAs($user)->postJson('/api/upgrade-requests', [
            'requested_role' => 'venue_admin',
            'business_name' => 'Sample Sports Hall',
            'phone' => '09120000000',
            'reason' => 'I want to register my venue.',
        ])->assertCreated()->json();

        $this->actingAs($admin)->patchJson('/api/admin/upgrade-requests/'.$request['id'], [
            'status' => 'approved',
            'admin_note' => 'Documents checked.',
        ])->assertOk()->assertJsonPath('status', 'approved');

        $this->assertDatabaseHas('users', ['id' => $user->id, 'role' => 'venue_admin']);
    }

    public function test_support_ticket_is_private_and_admin_can_reply_and_close_it(): void
    {
        $owner = $this->user('ticket-owner@example.test');
        $other = $this->user('other@example.test');
        $admin = $this->user('support@example.test', 'super_admin');

        $ticket = $this->actingAs($owner)->postJson('/api/support/tickets', [
            'subject' => 'Payment problem',
            'message' => 'My payment was completed but I need help.',
            'category' => 'payment',
            'priority' => 'high',
        ])->assertCreated()->json();

        $this->actingAs($other)->getJson('/api/support/tickets/'.$ticket['number'])->assertForbidden();

        $this->actingAs($admin)->postJson('/api/admin/support/tickets/'.$ticket['number'].'/messages', [
            'message' => 'We are checking your payment.',
        ])->assertCreated()->assertJsonPath('is_staff', true);

        $this->actingAs($admin)->patchJson('/api/admin/support/tickets/'.$ticket['number'].'/status', [
            'status' => 'closed',
        ])->assertOk()->assertJsonPath('status', 'closed');

        $this->assertDatabaseCount('support_messages', 2);
    }

    private function user(string $email, string $role = 'user'): User
    {
        return User::create([
            'name' => 'Test User',
            'email' => $email,
            'password' => 'password',
            'role' => $role,
        ]);
    }
}
