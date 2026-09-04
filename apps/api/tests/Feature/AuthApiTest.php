<?php

namespace Tests\Feature;

use App\Domain\Video\Models\Video;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_viewer_can_register_with_phone_and_password(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Awa Traoré',
            'phone' => '+223 76 00 00 00',
            'password' => '1234',
            'terms_accepted' => true,
        ])->assertCreated();

        $response->assertJsonPath('user.name', 'Awa Traoré');
        $response->assertJsonPath('user.phone', '+223 76 00 00 00');
        $response->assertJsonPath('user.role', 'viewer');
        $this->assertNotEmpty($response->json('token'));

        $this->assertDatabaseHas('users', [
            'phone' => '+223 76 00 00 00',
            'role' => 'viewer',
        ]);
    }

    public function test_registration_requires_a_unique_phone_number(): void
    {
        User::factory()->create(['phone' => '+223 76 00 00 00']);

        $this->postJson('/api/register', [
            'name' => 'Awa Traoré',
            'phone' => '+223 76 00 00 00',
            'password' => '1234',
            'terms_accepted' => true,
        ])->assertStatus(422);
    }

    public function test_a_registered_viewer_can_log_in_with_phone_and_password(): void
    {
        $this->postJson('/api/register', [
            'name' => 'Awa Traoré',
            'phone' => '+223 76 00 00 00',
            'password' => '1234',
            'terms_accepted' => true,
        ])->assertCreated();

        $response = $this->postJson('/api/login', [
            'phone' => '+223 76 00 00 00',
            'password' => '1234',
        ])->assertOk();

        $this->assertNotEmpty($response->json('token'));
    }

    public function test_login_fails_with_a_wrong_password(): void
    {
        User::factory()->create([
            'phone' => '+223 76 00 00 00',
            'password' => '1234',
            'role' => UserRole::Viewer,
        ]);

        $this->postJson('/api/login', [
            'phone' => '+223 76 00 00 00',
            'password' => '9999',
        ])->assertStatus(422);
    }

    public function test_registration_requires_a_4_digit_password(): void
    {
        $this->postJson('/api/register', [
            'name' => 'Awa Traoré',
            'phone' => '+223 76 00 00 00',
            'password' => '12345',
            'terms_accepted' => true,
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_registration_requires_accepting_the_terms(): void
    {
        $this->postJson('/api/register', [
            'name' => 'Awa Traoré',
            'phone' => '+223 76 00 00 00',
            'password' => '1234',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['terms_accepted']);
    }

    public function test_login_is_throttled_after_too_many_attempts(): void
    {
        User::factory()->create([
            'phone' => '+223 76 00 00 00',
            'password' => '1234',
            'role' => UserRole::Viewer,
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/login', [
                'phone' => '+223 76 00 00 00',
                'password' => '9999',
            ])->assertStatus(422);
        }

        $this->postJson('/api/login', [
            'phone' => '+223 76 00 00 00',
            'password' => '9999',
        ])->assertStatus(429);
    }

    public function test_an_authenticated_user_can_log_out(): void
    {
        $user = User::factory()->create(['role' => UserRole::Viewer]);
        $token = $user->createToken('api')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/logout')
            ->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_a_registered_viewer_can_use_the_token_to_purchase_a_video(): void
    {
        $registerResponse = $this->postJson('/api/register', [
            'name' => 'Awa Traoré',
            'phone' => '+223 76 00 00 00',
            'password' => '1234',
            'terms_accepted' => true,
        ])->assertCreated();

        $token = $registerResponse->json('token');

        Http::fake([
            '*/oauth/v3/token' => Http::response(['access_token' => 'fake-token'], 200),
            '*/webpayment' => Http::response([
                'payment_url' => 'https://webpay.orange-money.test/pay/abc123',
                'pay_token' => 'pay-token-abc123',
                'notif_token' => 'notif-token-abc123',
            ], 200),
        ]);

        $video = Video::factory()->approved()->create();

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/videos/{$video->id}/purchase", [
                'payer_msisdn' => '+223 76 00 00 00',
            ])
            ->assertCreated();
    }
}
