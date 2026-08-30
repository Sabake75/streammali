<?php

namespace Tests\Feature;

use App\Domain\Moderation\Enums\VideoStatus;
use App\Domain\Video\Models\Video;
use App\Enums\UserRole;
use App\Filament\Resources\Videos\Pages\ListVideos;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FeaturedVideoApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_featured_approved_videos_are_listed(): void
    {
        $featured = Video::factory()->approved()->create(['title' => 'En vedette', 'featured_at' => now()]);
        Video::factory()->approved()->create(['title' => 'Pas en vedette']);
        Video::factory()->create(['title' => 'Non approuvée', 'featured_at' => now()]);

        $response = $this->getJson('/api/videos/featured')->assertOk();

        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.title', 'En vedette');
    }

    public function test_featured_videos_are_ordered_by_most_recently_featured(): void
    {
        $olderFeatured = Video::factory()->approved()->create([
            'title' => 'Ancien',
            'featured_at' => now()->subDay(),
        ]);
        $recentlyFeatured = Video::factory()->approved()->create([
            'title' => 'Récent',
            'featured_at' => now(),
        ]);

        $response = $this->getJson('/api/videos/featured')->assertOk();

        $response->assertJsonPath('data.0.title', 'Récent');
        $response->assertJsonPath('data.1.title', 'Ancien');
    }

    public function test_moderator_can_feature_and_unfeature_an_approved_video_from_the_back_office(): void
    {
        $moderator = User::factory()->create(['role' => UserRole::Moderator]);
        $video = Video::factory()->approved()->create();

        Livewire::actingAs($moderator)
            ->test(ListVideos::class)
            ->mountTableAction('toggle_featured', $video)
            ->callMountedTableAction();

        $this->assertNotNull($video->fresh()->featured_at);

        Livewire::actingAs($moderator)
            ->test(ListVideos::class)
            ->mountTableAction('toggle_featured', $video)
            ->callMountedTableAction();

        $this->assertNull($video->fresh()->featured_at);
    }

    /**
     * The "En vedette" column itself is clickable (not just the
     * "toggle_featured" row action tucked in the "..." menu) — without a
     * dedicated column action, clicking it just opened the edit page
     * (Filament's default row-click behavior), which is what a moderator
     * actually hit in practice.
     */
    public function test_moderator_can_toggle_featured_by_clicking_the_column_icon(): void
    {
        $moderator = User::factory()->create(['role' => UserRole::Moderator]);
        $video = Video::factory()->approved()->create();

        Livewire::actingAs($moderator)
            ->test(ListVideos::class)
            ->callTableColumnAction('featured_at', $video);

        $this->assertNotNull($video->fresh()->featured_at);

        Livewire::actingAs($moderator)
            ->test(ListVideos::class)
            ->callTableColumnAction('featured_at', $video);

        $this->assertNull($video->fresh()->featured_at);
    }

    public function test_clicking_the_featured_column_on_a_non_approved_video_does_nothing(): void
    {
        $moderator = User::factory()->create(['role' => UserRole::Moderator]);
        $video = Video::factory()->create(['status' => VideoStatus::Pending]);

        Livewire::actingAs($moderator)
            ->test(ListVideos::class)
            ->callTableColumnAction('featured_at', $video);

        $this->assertNull($video->fresh()->featured_at);
    }
}
