<?php

namespace Tests\Feature;

use App\Domain\Moderation\Enums\VideoStatus;
use App\Domain\Video\Models\Video;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModerationVideoResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_moderator_can_view_the_video_moderation_queue(): void
    {
        $moderator = User::factory()->create(['role' => UserRole::Moderator]);
        Video::factory()->create(['title' => 'Sotigui le film']);

        $this->actingAs($moderator)
            ->get('/moderation/videos')
            ->assertOk()
            ->assertSee('Sotigui le film');
    }

    public function test_non_moderator_cannot_view_the_video_moderation_queue(): void
    {
        $viewer = User::factory()->create(['role' => UserRole::Viewer]);

        $this->actingAs($viewer)
            ->get('/moderation/videos')
            ->assertForbidden();
    }

    public function test_moderator_can_approve_a_pending_video(): void
    {
        $video = Video::factory()->create();

        $video->update(['status' => VideoStatus::Approved]);

        $this->assertSame(VideoStatus::Approved, $video->fresh()->status);
    }
}
