<?php

namespace Tests\Feature;

use App\Domain\Video\Models\Video;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VideoFavoriteApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_viewer_can_favorite_and_unfavorite_a_video(): void
    {
        $viewer = User::factory()->create(['role' => UserRole::Viewer]);
        $video = Video::factory()->approved()->create();

        $response = $this->actingAs($viewer, 'sanctum')
            ->postJson("/api/videos/{$video->id}/favorite")
            ->assertOk();

        $response->assertJsonPath('favorited', true);
        $this->assertDatabaseHas('favorites', ['user_id' => $viewer->id, 'video_id' => $video->id]);

        $response = $this->actingAs($viewer, 'sanctum')
            ->postJson("/api/videos/{$video->id}/favorite")
            ->assertOk();

        $response->assertJsonPath('favorited', false);
        $this->assertDatabaseMissing('favorites', ['user_id' => $viewer->id, 'video_id' => $video->id]);
    }

    public function test_a_guest_cannot_favorite_a_video(): void
    {
        $video = Video::factory()->approved()->create();

        $this->postJson("/api/videos/{$video->id}/favorite")->assertUnauthorized();
    }

    public function test_video_resource_exposes_favorited_state_for_authenticated_users(): void
    {
        $viewer = User::factory()->create(['role' => UserRole::Viewer]);
        $video = Video::factory()->approved()->create();
        $video->favorites()->create(['user_id' => $viewer->id]);

        $response = $this->actingAs($viewer, 'sanctum')
            ->getJson("/api/videos/{$video->id}")
            ->assertOk();

        $response->assertJsonPath('data.favorited', true);
    }

    public function test_a_viewer_can_list_their_favorited_videos(): void
    {
        $viewer = User::factory()->create(['role' => UserRole::Viewer]);
        $favorited = Video::factory()->approved()->create(['title' => 'Favorite']);
        $notFavorited = Video::factory()->approved()->create(['title' => 'Not favorite']);
        $favorited->favorites()->create(['user_id' => $viewer->id]);

        $response = $this->actingAs($viewer, 'sanctum')
            ->getJson('/api/favorites')
            ->assertOk();

        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.title', 'Favorite');
    }

    public function test_a_guest_cannot_list_favorites(): void
    {
        $this->getJson('/api/favorites')->assertUnauthorized();
    }
}
