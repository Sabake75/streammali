<?php

namespace Tests\Feature;

use App\Domain\Payment\Actions\ConfirmPayment;
use App\Domain\Payment\Actions\InitiatePayment;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Payment\Models\Payment;
use App\Domain\Video\Models\Video;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OrangeMoneyPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_initiate_payment_creates_a_pending_payment_and_returns_a_payment_url(): void
    {
        Http::fake([
            '*/oauth/v3/token' => Http::response(['access_token' => 'fake-token'], 200),
            '*/webpayment' => Http::response([
                'payment_url' => 'https://webpay.orange-money.test/pay/abc123',
                'pay_token' => 'pay-token-abc123',
            ], 200),
        ]);

        $buyer = User::factory()->create(['role' => UserRole::Viewer]);
        $video = Video::factory()->approved()->create(['price' => 25]);

        $result = app(InitiatePayment::class)($buyer, $video, '+223 76 00 00 00');

        $this->assertSame('https://webpay.orange-money.test/pay/abc123', $result->paymentUrl);
        $this->assertSame(PaymentStatus::Pending, $result->payment->status);
        $this->assertSame('pay-token-abc123', $result->payment->provider_pay_token);
        $this->assertSame(25, $result->payment->amount);

        Http::assertSent(fn ($request) => str_contains($request->url(), '/webpayment')
            && $request['order_id'] === $result->payment->order_reference);
    }

    public function test_confirm_payment_marks_a_verified_payment_as_succeeded(): void
    {
        Http::fake([
            '*/oauth/v3/token' => Http::response(['access_token' => 'fake-token'], 200),
            '*/transactionstatus*' => Http::response(['status' => 'SUCCESS'], 200),
        ]);

        $payment = Payment::factory()->create(['provider_pay_token' => 'pay-token-abc123']);

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

    public function test_orange_money_webhook_confirms_a_payment(): void
    {
        Http::fake([
            '*/oauth/v3/token' => Http::response(['access_token' => 'fake-token'], 200),
            '*/transactionstatus*' => Http::response(['status' => 'SUCCESS'], 200),
        ]);

        $payment = Payment::factory()->create(['provider_pay_token' => 'pay-token-abc123']);

        $this->postJson('/api/webhooks/orange-money', [
            'order_id' => $payment->order_reference,
            'status' => 'SUCCESS',
        ])->assertOk();

        $this->assertSame(PaymentStatus::Succeeded, $payment->fresh()->status);
    }
}
