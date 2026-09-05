<?php

namespace Tests\Feature;

use App\Domain\Payment\Actions\ConfirmPayment;
use App\Domain\Payment\Actions\InitiatePayment;
use App\Domain\Payment\Contracts\PaymentGateway;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Payment\Gateways\OrangeMoneyGateway;
use App\Domain\Payment\Models\LedgerEntry;
use App\Domain\Payment\Models\Payment;
use App\Domain\Video\Models\Video;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Orange Money is the active PaymentGateway binding (see AppServiceProvider),
 * but this test still pins the binding itself rather than relying on the
 * app's default, so it keeps passing if that default changes again.
 */
class OrangeMoneyPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->bind(PaymentGateway::class, OrangeMoneyGateway::class);
    }

    public function test_initiate_payment_creates_a_pending_payment_and_returns_a_payment_url(): void
    {
        Http::fake([
            '*/oauth/v3/token' => Http::response(['access_token' => 'fake-token'], 200),
            '*/webpayment' => Http::response([
                'payment_url' => 'https://webpay.orange-money.test/pay/abc123',
                'pay_token' => 'pay-token-abc123',
                'notif_token' => 'notif-token-abc123',
            ], 200),
        ]);

        $buyer = User::factory()->create(['role' => UserRole::Viewer]);
        $video = Video::factory()->approved()->create(['price' => 25]);

        $result = app(InitiatePayment::class)($buyer, $video, '+223 76 00 00 00');

        $this->assertSame('https://webpay.orange-money.test/pay/abc123', $result->paymentUrl);
        $this->assertSame(PaymentStatus::Pending, $result->payment->status);
        $this->assertSame('pay-token-abc123', $result->payment->provider_pay_token);
        $this->assertSame('notif-token-abc123', $result->payment->provider_notif_token);
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

    /**
     * Orange's own docs and this project's history (see notif_token fix)
     * both confirm the notif_url can be pinged more than once for the same
     * transaction. Regression test for the ledger side of ConfirmPayment's
     * idempotency — the status update alone being idempotent was never the
     * risky part, a second LedgerEntry silently double-crediting the
     * creator was (see ConfirmPayment/the ledger_entries unique index).
     */
    public function test_confirming_an_already_succeeded_payment_again_does_not_double_credit_the_ledger(): void
    {
        Http::fake([
            '*/oauth/v3/token' => Http::response(['access_token' => 'fake-token'], 200),
            '*/transactionstatus*' => Http::response(['status' => 'SUCCESS'], 200),
        ]);

        $video = Video::factory()->approved()->create(['price' => 1000]);
        $payment = Payment::factory()->create([
            'video_id' => $video->id,
            'amount' => 1000,
            'provider_pay_token' => 'pay-token-abc123',
        ]);

        app(ConfirmPayment::class)($payment);
        app(ConfirmPayment::class)($payment->fresh());

        $this->assertSame(1, LedgerEntry::where('payment_id', $payment->id)->count());
    }

    /** Defense-in-depth: the unique index itself, independent of the app-level guard above. */
    public function test_ledger_entries_payment_id_is_unique_at_the_database_level(): void
    {
        $payment = Payment::factory()->succeeded()->create();

        LedgerEntry::create([
            'creator_id' => $payment->video->creator_id,
            'payment_id' => $payment->id,
            'type' => 'sale',
            'gross_amount' => 100,
            'commission_amount' => 25,
            'net_amount' => 75,
        ]);

        $this->expectException(QueryException::class);

        LedgerEntry::create([
            'creator_id' => $payment->video->creator_id,
            'payment_id' => $payment->id,
            'type' => 'sale',
            'gross_amount' => 100,
            'commission_amount' => 25,
            'net_amount' => 75,
        ]);
    }

    public function test_orange_money_webhook_confirms_a_payment(): void
    {
        Http::fake([
            '*/oauth/v3/token' => Http::response(['access_token' => 'fake-token'], 200),
            '*/transactionstatus*' => Http::response(['status' => 'SUCCESS'], 200),
        ]);

        $payment = Payment::factory()->create([
            'provider_pay_token' => 'pay-token-abc123',
            'provider_notif_token' => 'notif-token-abc123',
        ]);

        // Orange's real notification body only carries {status, notif_token,
        // txnid} — no order_id (see App\Http\Controllers\Api\OrangeMoneyWebhookController).
        $this->postJson('/api/webhooks/orange-money', [
            'notif_token' => $payment->provider_notif_token,
            'status' => 'SUCCESS',
            'txnid' => 'MP150709.1341.A00073',
        ])->assertOk();

        $this->assertSame(PaymentStatus::Succeeded, $payment->fresh()->status);
        $this->assertSame('MP150709.1341.A00073', $payment->fresh()->provider_transaction_id);
    }
}
