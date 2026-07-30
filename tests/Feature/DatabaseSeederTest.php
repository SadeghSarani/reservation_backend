<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_seeders_are_complete_and_repeatable(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('users', 5);
        $this->assertDatabaseCount('venues', 12);
        $this->assertDatabaseCount('calendars', 7);
        $this->assertDatabaseCount('venue_time_prices', 504);

        foreach (['gym', 'tennis', 'football', 'basketball', 'futsal', 'volleyball'] as $type) {
            $this->assertSame(2, \App\Models\Venue::where('type', $type)->count());
        }
        $this->assertDatabaseCount('educational_classes', 4);

        $admin = User::where('email', 'admin@reservation.test')->firstOrFail();
        $this->assertSame('super_admin', $admin->role);
        $this->assertTrue(Hash::check('Admin@123456', $admin->password));
    }
}
