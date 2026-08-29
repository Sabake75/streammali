<?php

namespace Tests\Feature;

use App\Domain\Video\Models\Category;
use App\Domain\Video\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VideoCatalogApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalogue_only_lists_approved_videos(): void
    {
        Video::factory()->approved()->create(['title' => 'Film approuvé']);
        Video::factory()->create(['title' => 'Film en attente']);
        Video::factory()->rejected()->create(['title' => 'Film refusé']);

        $response = $this->getJson('/api/videos')->assertOk();

        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.title', 'Film approuvé');
    }

    public function test_catalogue_can_be_filtered_by_category(): void
    {
        Video::factory()->approved()->create(['category_id' => Category::where('slug', 'film')->value('id')]);
        Video::factory()->approved()->create(['category_id' => Category::where('slug', 'clip')->value('id')]);

        $response = $this->getJson('/api/videos?category=clip')->assertOk();

        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.category.value', 'clip');
    }

    public function test_catalogue_can_be_searched_by_title(): void
    {
        Video::factory()->approved()->create(['title' => 'Sotigui le film']);
        Video::factory()->approved()->create(['title' => 'Autre chose']);

        $response = $this->getJson('/api/videos?search=Sotigui')->assertOk();

        $response->assertJsonCount(1, 'data');
    }

    public function test_catalogue_search_is_case_insensitive(): void
    {
        Video::factory()->approved()->create(['title' => 'Sotigui le film']);

        $response = $this->getJson('/api/videos?search=SOTIGUI')->assertOk();

        $response->assertJsonCount(1, 'data');
    }

    public function test_catalogue_search_also_matches_the_description(): void
    {
        Video::factory()->approved()->create([
            'title' => 'Titre sans rapport',
            'description' => 'Un documentaire sur la vie à Bamako.',
        ]);

        $response = $this->getJson('/api/videos?search=Bamako')->assertOk();

        $response->assertJsonCount(1, 'data');
    }

    public function test_catalogue_search_also_matches_the_creator_name(): void
    {
        $creator = \App\Models\User::factory()->create([
            'role' => \App\Enums\UserRole::Creator,
            'name' => 'Fatoumata Diarra',
        ]);
        Video::factory()->approved()->create(['creator_id' => $creator->id, 'title' => 'Titre sans rapport']);
        Video::factory()->approved()->create(['title' => 'Autre chose']);

        $response = $this->getJson('/api/videos?search=Fatoumata')->assertOk();

        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.creator.name', 'Fatoumata Diarra');
    }

    public function test_catalogue_can_be_filtered_by_creator(): void
    {
        $creatorA = \App\Models\User::factory()->create(['role' => \App\Enums\UserRole::Creator]);
        $creatorB = \App\Models\User::factory()->create(['role' => \App\Enums\UserRole::Creator]);
        Video::factory()->approved()->create(['creator_id' => $creatorA->id]);
        Video::factory()->approved()->create(['creator_id' => $creatorB->id]);

        $response = $this->getJson("/api/videos?creator_id={$creatorA->id}")->assertOk();

        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.creator.id', $creatorA->id);
    }

    public function test_catalogue_can_be_sorted_by_popularity(): void
    {
        $lessViewed = Video::factory()->approved()->create(['title' => 'Peu vue', 'views_count' => 5]);
        $mostViewed = Video::factory()->approved()->create(['title' => 'Très vue', 'views_count' => 500]);

        $response = $this->getJson('/api/videos?sort=popular')->assertOk();

        $response->assertJsonPath('data.0.id', $mostViewed->id);
        $response->assertJsonPath('data.1.id', $lessViewed->id);
    }

    public function test_catalogue_sorts_by_recency_by_default(): void
    {
        $older = Video::factory()->approved()->create(['title' => 'Plus ancienne', 'created_at' => now()->subDay()]);
        $newer = Video::factory()->approved()->create(['title' => 'Plus récente', 'created_at' => now()]);

        $response = $this->getJson('/api/videos')->assertOk();

        $response->assertJsonPath('data.0.id', $newer->id);
        $response->assertJsonPath('data.1.id', $older->id);
    }

    public function test_show_returns_404_for_a_video_not_yet_approved(): void
    {
        $video = Video::factory()->create();

        $this->getJson("/api/videos/{$video->id}")->assertNotFound();
    }

    public function test_show_returns_the_video_when_approved(): void
    {
        $video = Video::factory()->approved()->create(['title' => 'Sotigui le film']);

        $this->getJson("/api/videos/{$video->id}")
            ->assertOk()
            ->assertJsonPath('data.title', 'Sotigui le film')
            ->assertJsonPath('data.creator.name', $video->creator->name);
    }
}
