<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EducationalClassTest extends TestCase
{
    use RefreshDatabase;

    public function test_instructor_can_publish_class_and_users_can_enroll_up_to_capacity(): void
    {
        config()->set('services.boometo.url', 'https://boometo.test');
        config()->set('services.boometo.token', 'secret');
        Http::fake([
            'https://boometo.test/transaction' => Http::response('<form>pay</form>', 200),
            'https://boometo.test/transaction/status' => Http::response(['trans_num' => 'CLASS-REF-1'], 200),
        ]);
        $instructor = $this->user('coach@example.test', 'instructor');
        $firstUser = $this->user('student1@example.test');
        $secondUser = $this->user('student2@example.test');

        $class = $this->actingAs($instructor)->postJson('/api/manage/educational-classes', [
            'title' => 'Beginner Tennis',
            'description' => 'A complete beginner tennis course.',
            'category' => 'tennis',
            'level' => 'beginner',
            'capacity' => 1,
            'price' => 500000,
            'location' => 'Azadi Sports Complex',
            'schedule' => [['day' => 'شنبه', 'start_time' => '10:00', 'end_time' => '11:30']],
            'features' => ['Certificate', 'Training equipment'],
            'registration_deadline' => '2026-08-30 23:59:00',
            'starts_on' => '2026-09-01',
            'ends_on' => '2026-10-01',
            'status' => 'published',
        ])->assertCreated()->json();

        $this->getJson('/api/educational-classes/'.$class['slug'])
            ->assertOk()->assertJsonPath('available_capacity', 1);

        $payment = $this->actingAs($firstUser)->postJson('/api/educational-classes/'.$class['slug'].'/enroll')
            ->assertCreated()->assertJsonPath('amount', '500000.00')
            ->assertJsonStructure(['invoice', 'payment_url'])->json();

        $this->assertDatabaseCount('educational_class_enrollments', 0);

        $this->actingAs($secondUser)->postJson('/api/educational-classes/'.$class['slug'].'/enroll')
            ->assertStatus(409);

        $this->get('/api/v1/payments/boometo/callback?invoice='.$payment['invoice'])
            ->assertRedirectContains('status=true')
            ->assertRedirectContains('type=educational_class');
        $this->assertDatabaseHas('educational_class_enrollments', [
            'educational_class_id' => $class['id'], 'user_id' => $firstUser->id,
            'status' => 'registered', 'payment_status' => 'paid',
        ]);

        $this->actingAs($instructor)->getJson('/api/manage/educational-classes/analytics')
            ->assertOk()
            ->assertJsonPath('summary.total_registrations', 1)
            ->assertJsonPath('best_class.id', $class['id']);
    }

    public function test_regular_user_cannot_create_an_educational_class(): void
    {
        $this->actingAs($this->user('normal@example.test'))
            ->postJson('/api/manage/educational-classes', [])
            ->assertForbidden();
    }

    private function user(string $email, string $role = 'user'): User
    {
        return User::create(['name' => 'Test User', 'email' => $email, 'password' => 'password', 'role' => $role]);
    }
}
