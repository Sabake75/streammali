<?php

namespace Tests\Feature;

use App\Domain\Moderation\Enums\VideoStatus;
use App\Domain\Video\Models\Video;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreatorVideoUploadApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_upload_a_video(): void
    {
        $this->postJson('/api/creator/videos', [
            'title' => 'Sotigui le film',
            'category' => 'film',
        ])->assertUnauthorized();
    }

    public function test_a_viewer_cannot_upload_a_video(): void
    {
        $viewer = User::factory()->create(['role' => UserRole::Viewer]);

        $this->actingAs($viewer, 'sanctum')
            ->postJson('/api/creator/videos', [
                'title' => 'Sotigui le film',
                'category' => 'film',
            ])->assertForbidden();
    }

    public function test_a_creator_can_upload_a_video_which_starts_out_pending(): void
    {
        $creator = User::factory()->create(['role' => UserRole::Creator]);

        $response = $this->actingAs($creator, 'sanctum')
            ->postJson('/api/creator/videos', [
                'title' => 'Sotigui le film',
                'description' => 'Un film malien.',
                'category' => 'film',
                'duration_seconds' => 5400,
            ])
            ->assertCreated();

        $response->assertJsonPath('data.title', 'Sotigui le film');
        $response->assertJsonPath('data.status.value', 'pending');
        $response->assertJsonPath('data.price', 100);

        $this->assertDatabaseHas('videos', [
            'creator_id' => $creator->id,
            'title' => 'Sotigui le film',
            'status' => 'pending',
        ]);
    }

    public function test_upload_validates_required_fields(): void
    {
        $creator = User::factory()->create(['role' => UserRole::Creator]);

        $this->actingAs($creator, 'sanctum')
            ->postJson('/api/creator/videos', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'category']);
    }

    public function test_a_creator_can_list_their_own_videos_regardless_of_status(): void
    {
        $creator = User::factory()->create(['role' => UserRole::Creator]);
        $otherCreator = User::factory()->create(['role' => UserRole::Creator]);

        Video::factory()->for($creator, 'creator')->create(['title' => 'En attente']);
        Video::factory()->for($creator, 'creator')->rejected()->create(['title' => 'Refusée']);
        Video::factory()->for($otherCreator, 'creator')->approved()->create(['title' => 'Pas la mienne']);

        $response = $this->actingAs($creator, 'sanctum')
            ->getJson('/api/creator/videos')
            ->assertOk();

        $response->assertJsonCount(2, 'data');
        $titles = collect($response->json('data'))->pluck('title');
        $this->assertTrue($titles->contains('En attente'));
        $this->assertTrue($titles->contains('Refusée'));
        $this->assertFalse($titles->contains('Pas la mienne'));
    }
}
