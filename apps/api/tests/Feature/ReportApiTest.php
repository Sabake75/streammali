<?php

namespace Tests\Feature;

use App\Domain\Moderation\Enums\ReportStatus;
use App\Domain\Moderation\Enums\VideoStatus;
use App\Domain\Video\Models\Video;
use App\Enums\UserRole;
use App\Filament\Resources\Videos\Pages\ListVideos;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReportApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_viewer_can_report_an_approved_video(): void
    {
        $viewer = User::factory()->create(['role' => UserRole::Viewer]);
        $video = Video::factory()->approved()->create();

        $response = $this->actingAs($viewer, 'sanctum')
            ->postJson("/api/videos/{$video->id}/report", ['reason' => 'Contenu inapproprié.'])
            ->assertCreated();

        $response->assertJsonStructure(['id', 'message']);

        $this->assertDatabaseHas('reports', [
            'video_id' => $video->id,
            'reporter_id' => $viewer->id,
            'reason' => 'Contenu inapproprié.',
            'status' => 'pending',
        ]);
    }

    public function test_reporting_a_video_requires_a_reason(): void
    {
        $viewer = User::factory()->create(['role' => UserRole::Viewer]);
        $video = Video::factory()->approved()->create();

        $this->actingAs($viewer, 'sanctum')
            ->postJson("/api/videos/{$video->id}/report", ['reason' => ''])
            ->assertStatus(422);
    }

    public function test_a_guest_cannot_report_a_video(): void
    {
        $video = Video::factory()->approved()->create();

        $this->postJson("/api/videos/{$video->id}/report", ['reason' => 'test'])
            ->assertUnauthorized();
    }

    public function test_moderator_sees_a_reported_video_flagged_in_the_queue(): void
    {
        $moderator = User::factory()->create(['role' => UserRole::Moderator]);
        $video = Video::factory()->approved()->create(['title' => 'Vidéo signalée']);
        $video->reports()->create([
            'reporter_id' => User::factory()->create(['role' => UserRole::Viewer])->id,
            'reason' => 'Contenu volé.',
        ]);

        $this->actingAs($moderator)
            ->get('/moderation/videos')
            ->assertOk()
            ->assertSee('Vidéo signalée');
    }

    public function test_moderator_can_dismiss_reports_from_the_back_office(): void
    {
        $moderator = User::factory()->create(['role' => UserRole::Moderator]);
        $video = Video::factory()->approved()->create();
        $report = $video->reports()->create([
            'reporter_id' => User::factory()->create(['role' => UserRole::Viewer])->id,
            'reason' => 'Contenu volé.',
        ]);

        Livewire::actingAs($moderator)
            ->test(ListVideos::class)
            ->mountTableAction('reports', $video)
            ->callMountedTableAction();

        $this->assertSame(ReportStatus::Dismissed, $report->fresh()->status);
    }

    public function test_moderator_can_unpublish_a_reported_video_via_the_existing_reject_action(): void
    {
        $video = Video::factory()->approved()->create();
        $video->reports()->create([
            'reporter_id' => User::factory()->create(['role' => UserRole::Viewer])->id,
            'reason' => 'Contenu volé.',
        ]);

        $video->update(['status' => VideoStatus::Rejected, 'rejection_reason' => 'Signalement fondé — contenu volé.']);

        $this->assertSame(VideoStatus::Rejected, $video->fresh()->status);
        $this->assertDatabaseHas('videos', [
            'id' => $video->id,
            'status' => 'rejected',
        ]);
    }
}
