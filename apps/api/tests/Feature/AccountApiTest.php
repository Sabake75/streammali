<?php

namespace Tests\Feature;

use App\Domain\Moderation\Enums\AccountStatus;
use App\Domain\Moderation\Enums\VideoStatus;
use App\Domain\Payment\Actions\ConfirmPayment;
use App\Domain\Payment\Models\Payment;
use App\Domain\Video\Models\Video;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AccountApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_viewer_can_export_their_data(): void
    {
        $viewer = User::factory()->create(['role' => UserRole::Viewer, 'phone' => '+22370000001']);
        $video = Video::factory()->state(['status' => VideoStatus::Approved])->create();
        Payment::factory()->create(['buyer_id' => $viewer->id, 'video_id' => $video->id]);

        $response = $this->actingAs($viewer, 'sanctum')->getJson('/api/account/export');

        $response->assertOk();
        $response->assertJsonPath('profile.id', $viewer->id);
        $response->assertJsonCount(1, 'purchases');
        $response->assertJsonMissingPath('videos');
    }

    public function test_a_creator_export_includes_their_creator_only_data(): void
    {
        $creator = User::factory()->create(['role' => UserRole::Creator]);
        Video::factory()->for($creator, 'creator')->create();

        $response = $this->actingAs($creator, 'sanctum')->getJson('/api/account/export');

        $response->assertOk();
        $response->assertJsonCount(1, 'videos');
        $response->assertJsonStructure(['ledger_entries', 'payouts', 'messages']);
    }

    public function test_a_viewer_can_delete_their_account(): void
    {
        $viewer = User::factory()->create([
            'role' => UserRole::Viewer,
            'phone' => '+22370000002',
            'email' => 'viewer@example.com',
        ]);
        $viewer->createToken('api');

        $response = $this->actingAs($viewer, 'sanctum')->deleteJson('/api/account');

        $response->assertOk();

        $viewer->refresh();
        $this->assertSame(AccountStatus::Deleted, $viewer->account_status);
        $this->assertNull($viewer->phone);
        $this->assertNull($viewer->email);
        $this->assertSame('Compte supprimé', $viewer->name);
        $this->assertSame(0, $viewer->tokens()->count());
    }

    public function test_deleting_the_account_deletes_the_stored_identity_document(): void
    {
        Storage::fake(config('filesystems.default'));
        $path = UploadedFile::fake()->create('cni.pdf')->store('identity-documents', config('filesystems.default'));
        $creator = User::factory()->create([
            'role' => UserRole::Creator,
            'identity_document_path' => $path,
        ]);

        $this->actingAs($creator, 'sanctum')->deleteJson('/api/account')->assertOk();

        Storage::disk(config('filesystems.default'))->assertMissing($path);
    }

    public function test_a_creator_with_an_available_balance_cannot_delete_their_account_yet(): void
    {
        Http::fake([
            '*/checkout-invoice/confirm/*' => Http::response(['status' => 'completed'], 200),
        ]);

        $creator = User::factory()->create(['role' => UserRole::Creator]);
        $video = Video::factory()->for($creator, 'creator')->state(['status' => VideoStatus::Approved, 'price' => 1000])->create();
        $payment = Payment::factory()->create([
            'video_id' => $video->id,
            'amount' => 1000,
            'provider_pay_token' => 'pay-token',
        ]);
        app(ConfirmPayment::class)($payment);

        $response = $this->actingAs($creator, 'sanctum')->deleteJson('/api/account');

        $response->assertStatus(422);
        $this->assertSame(AccountStatus::Active, $creator->fresh()->account_status);
    }

    public function test_a_deleted_account_cannot_log_back_in(): void
    {
        $viewer = User::factory()->create(['role' => UserRole::Viewer, 'phone' => '+22370000003']);
        $this->actingAs($viewer, 'sanctum')->deleteJson('/api/account')->assertOk();

        $response = $this->postJson('/api/login', ['phone' => '+22370000003', 'password' => '1234']);

        $response->assertUnprocessable();
    }
}
