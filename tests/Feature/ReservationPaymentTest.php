<?php

namespace Tests\Feature;

use App\Models\Calendar;
use App\Models\User;
use App\Models\Venue;
use App\Models\VenueTimePrice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ReservationPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_reservation_is_created_only_after_successful_payment(): void
    {
        config()->set('services.boometo.url', 'https://boometo.test');
        config()->set('services.boometo.token', 'secret');
        Http::fake([
            'https://boometo.test/transaction' => Http::response('<form>pay</form>', 200),
            'https://boometo.test/transaction/status' => Http::response(['trans_num' => 'REF-1'], 200),
        ]);

        $user = User::create([
            'name' => 'Payment user', 'email' => 'payer@example.test', 'password' => 'password', 'role' => 'user',
        ]);
        $venue = Venue::create([
            'owner_id' => $user->id, 'name' => 'Test venue', 'type' => 'futsal',
            'billing_type' => 'hourly', 'is_active' => true, 'price' => 100,
            'additionals' => [['name' => 'ball', 'price' => 20]],
        ]);
        $calendar = Calendar::create(['day' => '2026-08-01', 'holiday' => false]);
        $interval = VenueTimePrice::create([
            'venue_id' => $venue->id, 'calendar_id' => $calendar->id,
            'start_time' => '10:00', 'end_time' => '11:30', 'price' => 100,
        ]);

        $response = $this->actingAs($user)->postJson('/api/reservations', [
            'calendar_interval_id' => $interval->id,
            'additionals' => [['name' => 'ball', 'price' => 20]],
        ])->assertCreated()->assertJsonStructure(['invoice', 'amount', 'payment_url']);

        $this->assertDatabaseCount('reservations', 0);

        $this->getJson('/api/v1/payments/boometo/callback?invoice='.$response->json('invoice'))
            ->assertRedirectContains('/payment/callback?status=true');

        $this->assertDatabaseHas('reservations', [
            'user_id' => $user->id,
            'calendar_interval_id' => $interval->id,
            'status' => 'confirmed',
            'total_price' => 120,
        ]);

        // Provider callbacks are commonly retried and must stay idempotent.
        $this->getJson('/api/v1/payments/boometo/callback?invoice='.$response->json('invoice'))
            ->assertRedirectContains('status=true');
        $this->assertDatabaseCount('reservations', 1);
    }
}
