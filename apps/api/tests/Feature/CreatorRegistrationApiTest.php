<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CreatorRegistrationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_creator_can_register_with_an_identity_document(): void
    {
        Storage::fake('local');

        $response = $this->post('/api/register/creator', [
            'name' => 'Fatoumata Diarra',
            'phone' => '+223 65 11 22 33',
            'password' => '1234',
            'identity_document' => UploadedFile::fake()->image('cni.jpg'),
            'terms_accepted' => true,
        ])->assertCreated();

        $response->assertJsonPath('user.role', 'creator');
        $this->assertNotEmpty($response->json('token'));

        $user = User::where('phone', '+223 65 11 22 33')->firstOrFail();
        $this->assertSame(UserRole::Creator, $user->role);
        $this->assertNotNull($user->identity_document_path);
        Storage::disk('local')->assertExists($user->identity_document_path);
    }

    public function test_registration_fails_without_an_identity_document(): void
    {
        $this->postJson('/api/register/creator', [
            'name' => 'Fatoumata Diarra',
            'phone' => '+223 65 11 22 33',
            'password' => '1234',
            'terms_accepted' => true,
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['identity_document']);
    }

    public function test_registration_requires_accepting_the_terms(): void
    {
        Storage::fake('local');

        $this->post('/api/register/creator', [
            'name' => 'Fatoumata Diarra',
            'phone' => '+223 65 11 22 33',
            'password' => '1234',
            'identity_document' => UploadedFile::fake()->image('cni.jpg'),
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['terms_accepted']);
    }

    public function test_registration_rejects_a_disallowed_file_type(): void
    {
        Storage::fake('local');

        $this->postJson('/api/register/creator', [
            'name' => 'Fatoumata Diarra',
            'phone' => '+223 65 11 22 33',
            'password' => '1234',
            'identity_document' => UploadedFile::fake()->create('script.exe', 10),
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['identity_document']);
    }

    public function test_moderator_can_download_a_creators_identity_document(): void
    {
        Storage::fake('local');
        $moderator = User::factory()->create(['role' => UserRole::Moderator]);
        $creator = User::factory()->create([
            'role' => UserRole::Creator,
            'identity_document_path' => 'identity-documents/example.jpg',
        ]);
        Storage::disk('local')->put('identity-documents/example.jpg', 'fake-image-bytes');

        $this->actingAs($moderator)
            ->get("/moderation/creators/{$creator->id}/identity-document")
            ->assertOk();
    }

    public function test_a_viewer_cannot_download_a_creators_identity_document(): void
    {
        Storage::fake('local');
        $viewer = User::factory()->create(['role' => UserRole::Viewer]);
        $creator = User::factory()->create([
            'role' => UserRole::Creator,
            'identity_document_path' => 'identity-documents/example.jpg',
        ]);

        $this->actingAs($viewer)
            ->get("/moderation/creators/{$creator->id}/identity-document")
            ->assertForbidden();
    }

    public function test_a_guest_cannot_download_a_creators_identity_document(): void
    {
        $creator = User::factory()->create([
            'role' => UserRole::Creator,
            'identity_document_path' => 'identity-documents/example.jpg',
        ]);

        $this->get("/moderation/creators/{$creator->id}/identity-document")
            ->assertUnauthorized();
    }

    public function test_a_viewer_can_upgrade_to_creator_without_creating_a_second_account(): void
    {
        Storage::fake('local');
        $viewer = User::factory()->create(['role' => UserRole::Viewer, 'phone' => '+223 65 11 22 33']);

        $response = $this->actingAs($viewer, 'sanctum')
            ->post('/api/creator/upgrade', [
                'identity_document' => UploadedFile::fake()->image('cni.jpg'),
                'terms_accepted' => true,
            ])
            ->assertOk();

        $response->assertJsonPath('user.id', $viewer->id);
        $response->assertJsonPath('user.role', 'creator');

        $viewer->refresh();
        $this->assertSame(UserRole::Creator, $viewer->role);
        $this->assertSame('+223 65 11 22 33', $viewer->phone);
        $this->assertNotNull($viewer->identity_document_path);
        $this->assertNotNull($viewer->terms_accepted_at);
        Storage::disk('local')->assertExists($viewer->identity_document_path);
        $this->assertSame(1, User::count());
    }

    public function test_upgrading_to_creator_requires_an_identity_document(): void
    {
        $viewer = User::factory()->create(['role' => UserRole::Viewer]);

        $this->actingAs($viewer, 'sanctum')
            ->postJson('/api/creator/upgrade', ['terms_accepted' => true])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['identity_document']);
    }

    public function test_an_already_creator_account_cannot_upgrade_again(): void
    {
        Storage::fake('local');
        $creator = User::factory()->create(['role' => UserRole::Creator]);

        $this->actingAs($creator, 'sanctum')
            ->post('/api/creator/upgrade', [
                'identity_document' => UploadedFile::fake()->image('cni.jpg'),
                'terms_accepted' => true,
            ])
            ->assertStatus(409);
    }

    public function test_a_moderator_cannot_upgrade_to_creator(): void
    {
        Storage::fake('local');
        $moderator = User::factory()->create(['role' => UserRole::Moderator]);

        $this->actingAs($moderator, 'sanctum')
            ->post('/api/creator/upgrade', [
                'identity_document' => UploadedFile::fake()->image('cni.jpg'),
                'terms_accepted' => true,
            ])
            ->assertForbidden();
    }

    public function test_a_guest_cannot_upgrade_to_creator(): void
    {
        $this->postJson('/api/creator/upgrade', ['terms_accepted' => true])
            ->assertUnauthorized();
    }
}
