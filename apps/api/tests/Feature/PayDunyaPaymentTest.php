<?php

namespace Tests\Feature;

use App\Domain\Payment\Actions\ConfirmPayment;
use App\Domain\Payment\Actions\InitiatePayment;
use App\Domain\Payment\Contracts\PaymentGateway;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Payment\Gateways\PayDunyaGateway;
use App\Domain\Payment\Models\Payment;
use App\Domain\Video\Models\Video;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * PayDunya is no longer the active PaymentGateway binding (Orange Money is
 * — see AppServiceProvider) but stays in the codebase as an alternate
 * implementation of the same interface, so this test pins the binding
 * itself rather than relying on the app's default.
 */
class PayDunyaPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->bind(PaymentGateway::class, PayDunyaGateway::class);
    }

    public function test_initiate_payment_creates_a_pending_payment_and_returns_a_payment_url(): void
    {
        Http::fake([
            '*/checkout-invoice/create' => Http::response([
                'response_code' => '00',
                'response_text' => 'Invoice Created',
                'token' => 'invoice-token-abc123',
            ], 200),
        ]);

        $buyer = User::factory()->create(['role' => UserRole::Viewer]);
        $video = Video::factory()->approved()->create(['price' => 25]);

        $result = app(InitiatePayment::class)($buyer, $video, '+223 76 00 00 00');

        $this->assertSame('https://paydunya.com/checkout/invoice/invoice-token-abc123', $result->paymentUrl);
        $this->assertSame(PaymentStatus::Pending, $result->payment->status);
        $this->assertSame('invoice-token-abc123', $result->payment->provider_pay_token);
        $this->assertSame(25, $result->payment->amount);

        Http::assertSent(fn ($request) => str_contains($request->url(), '/checkout-invoice/create')
            && $request['custom_data']['order_reference'] === $result->payment->order_reference);
    }

    public function test_initiate_payment_fails_loudly_on_a_paydunya_error_response(): void
    {
        Http::fake([
            '*/checkout-invoice/create' => Http::response([
                'response_code' => '01',
                'response_text' => 'Invalid credentials',
            ], 200),
        ]);

        $buyer = User::factory()->create(['role' => UserRole::Viewer]);
        $video = Video::factory()->approved()->create();

        $this->expectExceptionMessage('PayDunya invoice creation failed: Invalid credentials');

        app(InitiatePayment::class)($buyer, $video, '+223 76 00 00 00');
    }

    public function test_purchasing_a_video_returns_a_friendly_error_when_paydunya_refuses_the_request(): void
    {
        // Reproduces the real production incident: PayDunya's merchant
        // account was blocked pending KYC, so every invoice creation
        // returned an HTTP 200 with response_code != "00" — a business
        // refusal, not a transport failure. Without the
        // PaymentGatewayException -> render() handler in bootstrap/app.php,
        // this surfaced to the viewer as a raw, untranslated "Server Error".
        Http::fake([
            '*/checkout-invoice/create' => Http::response([
                'response_code' => '01',
                'response_text' => 'Vous devez valider vos informations de KYC avant d\'avoir accès au service.',
            ], 200),
        ]);

        $buyer = User::factory()->create(['role' => UserRole::Viewer]);
        $video = Video::factory()->approved()->create();

        $this->actingAs($buyer)
            ->postJson("/api/videos/{$video->id}/purchase", ['payer_msisdn' => '+223 76 00 00 00'])
            ->assertStatus(502)
            ->assertJson(['message' => 'Un service externe est momentanément indisponible. Réessaie dans quelques instants.']);
    }

    public function test_confirm_payment_marks_a_verified_payment_as_succeeded(): void
    {
        Http::fake([
            '*/checkout-invoice/confirm/*' => Http::response(['status' => 'completed'], 200),
        ]);

        $payment = Payment::factory()->create(['provider_pay_token' => 'invoice-token-abc123']);

        $confirmed = app(ConfirmPayment::class)($payment);

        $this->assertSame(PaymentStatus::Succeeded, $confirmed->status);
        $this->assertNotNull($confirmed->confirmed_at);
    }

    public function test_confirm_payment_is_idempotent_and_does_not_reverify_an_already_succeeded_payment(): void
    {
        Http::fake();

        $payment = Payment::factory()->succeeded()->create();

        app(ConfirmPayment::class)($payment);

        Http::assertNothingSent();
    }

    public function test_paydunya_webhook_confirms_a_payment(): void
    {
        Http::fake([
            '*/checkout-invoice/confirm/*' => Http::response(['status' => 'completed'], 200),
        ]);

        $payment = Payment::factory()->create(['provider_pay_token' => 'invoice-token-abc123']);

        $this->postJson('/api/webhooks/paydunya', [
            'data' => json_encode([
                'custom_data' => ['order_reference' => $payment->order_reference],
                'status' => 'completed',
            ]),
        ])->assertOk();

        $this->assertSame(PaymentStatus::Succeeded, $payment->fresh()->status);
    }
}
