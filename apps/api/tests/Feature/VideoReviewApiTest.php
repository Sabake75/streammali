<?php

namespace Tests\Feature;

use App\Domain\Payment\Models\Payment;
use App\Domain\Video\Models\Video;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VideoReviewApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_buyer_can_review_a_video_they_purchased(): void
    {
        $buyer = User::factory()->create(['role' => UserRole::Viewer]);
        $video = Video::factory()->approved()->create();
        Payment::factory()->succeeded()->for($buyer, 'buyer')->for($video)->create();

        $response = $this->actingAs($buyer, 'sanctum')
            ->postJson("/api/videos/{$video->id}/reviews", ['rating' => 5, 'comment' => 'Excellent !'])
            ->assertCreated();

        $response->assertJsonPath('data.rating', 5);
        $response->assertJsonPath('data.comment', 'Excellent !');
        $response->assertJsonPath('data.user.id', $buyer->id);

        $this->assertDatabaseHas('reviews', [
            'video_id' => $video->id,
            'user_id' => $buyer->id,
            'rating' => 5,
            'comment' => 'Excellent !',
        ]);
    }

    public function test_resubmitting_a_review_replaces_it_instead_of_duplicating(): void
    {
        $buyer = User::factory()->create(['role' => UserRole::Viewer]);
        $video = Video::factory()->approved()->create();
        Payment::factory()->succeeded()->for($buyer, 'buyer')->for($video)->create();

        $this->actingAs($buyer, 'sanctum')
            ->postJson("/api/videos/{$video->id}/reviews", ['rating' => 2, 'comment' => 'Bof.'])
            ->assertCreated();

        $this->actingAs($buyer, 'sanctum')
            ->postJson("/api/videos/{$video->id}/reviews", ['rating' => 4, 'comment' => 'Finalement pas mal.'])
            ->assertOk();

        $this->assertDatabaseCount('reviews', 1);
        $this->assertDatabaseHas('reviews', [
            'video_id' => $video->id,
            'user_id' => $buyer->id,
            'rating' => 4,
            'comment' => 'Finalement pas mal.',
        ]);
    }

    public function test_a_viewer_who_has_not_purchased_the_video_cannot_review_it(): void
    {
        $viewer = User::factory()->create(['role' => UserRole::Viewer]);
        $video = Video::factory()->approved()->create();

        $this->actingAs($viewer, 'sanctum')
            ->postJson("/api/videos/{$video->id}/reviews", ['rating' => 5])
            ->assertForbidden();

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_a_guest_cannot_review_a_video(): void
    {
        $video = Video::factory()->approved()->create();

        $this->postJson("/api/videos/{$video->id}/reviews", ['rating' => 5])
            ->assertUnauthorized();
    }

    public function test_rating_must_be_between_1_and_5(): void
    {
        $buyer = User::factory()->create(['role' => UserRole::Viewer]);
        $video = Video::factory()->approved()->create();
        Payment::factory()->succeeded()->for($buyer, 'buyer')->for($video)->create();

        $this->actingAs($buyer, 'sanctum')
            ->postJson("/api/videos/{$video->id}/reviews", ['rating' => 6])
            ->assertStatus(422);
    }

    public function test_reviews_are_listed_publicly(): void
    {
        $buyer = User::factory()->create(['role' => UserRole::Viewer, 'name' => 'Awa']);
        $video = Video::factory()->approved()->create();
        $video->reviews()->create(['user_id' => $buyer->id, 'rating' => 5, 'comment' => 'Génial']);

        $response = $this->getJson("/api/videos/{$video->id}/reviews")->assertOk();

        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.user.name', 'Awa');
    }

    public function test_video_show_exposes_average_rating_and_reviews_count(): void
    {
        $video = Video::factory()->approved()->create();
        $video->reviews()->create([
            'user_id' => User::factory()->create()->id,
            'rating' => 4,
        ]);
        $video->reviews()->create([
            'user_id' => User::factory()->create()->id,
            'rating' => 2,
        ]);

        $response = $this->getJson("/api/videos/{$video->id}")->assertOk();

        $this->assertEquals(3.0, $response->json('data.average_rating'));
        $response->assertJsonPath('data.reviews_count', 2);
    }
}
