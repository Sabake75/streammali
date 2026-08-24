<?php

namespace Tests\Feature;

use App\Domain\Video\Models\Video;
use App\Enums\UserRole;
use App\Filament\Resources\Videos\Pages\ListVideos;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class WatchButtonTest extends TestCase
{
    use RefreshDatabase;

    public function test_moderator_can_watch_a_ready_video_before_validating_it(): void
    {
        $moderator = User::factory()->create(['role' => UserRole::Moderator]);
        $video = Video::factory()->create([
            'source_status' => 'ready',
            'playback_url' => 'https://customer-12zsknd2ybozi01a.cloudflarestream.com/ad1866b88ff8a26a06683e9deb6c8345/manifest/video.m3u8',
        ]);

        $component = Livewire::actingAs($moderator)
            ->test(ListVideos::class)
            ->assertTableActionVisible('watch', $video)
            ->mountTableAction('watch', $video);

        $instance = $component->instance();
        $schema = $instance->getSchema($instance->getMountedActionSchemaName());
        $viewData = $schema->getComponents()[0]->getViewData();

        $this->assertSame(
            'https://customer-12zsknd2ybozi01a.cloudflarestream.com/ad1866b88ff8a26a06683e9deb6c8345/iframe',
            $viewData['src'],
        );

        // The iframe must survive Blade rendering — TextEntry::html() would
        // silently strip it via Filament's HTML sanitizer, leaving an empty
        // modal with no error.
        $this->assertStringContainsString('<iframe', $schema->getComponents()[0]->toHtml());
    }

    public function test_watch_button_is_hidden_until_the_video_file_is_ready(): void
    {
        $moderator = User::factory()->create(['role' => UserRole::Moderator]);
        $video = Video::factory()->create(['source_status' => 'processing', 'playback_url' => null]);

        Livewire::actingAs($moderator)
            ->test(ListVideos::class)
            ->assertTableActionHidden('watch', $video);
    }
}
