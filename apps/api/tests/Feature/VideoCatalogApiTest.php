<?php

namespace Tests\Feature;

use App\Domain\Video\Enums\VideoCategory;
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
        Video::factory()->approved()->create(['category' => VideoCategory::Film]);
        Video::factory()->approved()->create(['category' => VideoCategory::Clip]);

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
