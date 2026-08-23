<?php

namespace Tests\Feature;

use App\Domain\Payment\Models\Payment;
use App\Domain\Video\Models\Category;
use App\Domain\Video\Models\Video;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VideoRecommendationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_guest_gets_the_most_popular_approved_videos(): void
    {
        Video::factory()->approved()->create(['title' => 'Populaire', 'views_count' => 100]);
        Video::factory()->approved()->create(['title' => 'Peu vue', 'views_count' => 1]);
        Video::factory()->create(['title' => 'Pas approuvée', 'views_count' => 1000]);

        $response = $this->getJson('/api/videos/recommended')->assertOk();

        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('data.0.title', 'Populaire');
    }

    public function test_a_viewer_gets_videos_from_categories_they_bought_or_favorited(): void
    {
        $viewer = User::factory()->create(['role' => UserRole::Viewer]);
        $film = Category::where('slug', 'film')->first();
        $clip = Category::where('slug', 'clip')->first();
        $sketch = Category::where('slug', 'sketch')->first();

        $bought = Video::factory()->approved()->create(['category_id' => $film->id]);
        Payment::factory()->succeeded()->for($viewer, 'buyer')->for($bought)->create();

        $sameCategoryAsBought = Video::factory()->approved()->create([
            'category_id' => $film->id,
            'views_count' => 50,
        ]);

        $favoritedVideo = Video::factory()->approved()->create(['category_id' => $clip->id]);
        $favoritedVideo->favorites()->create(['user_id' => $viewer->id]);

        $sameCategoryAsFavorited = Video::factory()->approved()->create([
            'category_id' => $clip->id,
            'views_count' => 20,
        ]);

        $unrelated = Video::factory()->approved()->create(['category_id' => $sketch->id, 'views_count' => 1000]);

        $response = $this->actingAs($viewer, 'sanctum')
            ->getJson('/api/videos/recommended')
            ->assertOk();

        $titles = collect($response->json('data'))->pluck('title');

        $this->assertTrue($titles->contains($sameCategoryAsBought->title));
        $this->assertTrue($titles->contains($sameCategoryAsFavorited->title));
        $this->assertFalse($titles->contains($unrelated->title));
        // Already owned — not recommended back to them.
        $this->assertFalse($titles->contains($bought->title));
    }
}
