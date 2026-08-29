<?php

namespace Tests\Feature;

use App\Domain\Moderation\Actions\SendMessage;
use App\Domain\Moderation\Enums\VideoStatus;
use App\Domain\Video\Enums\VideoSourceStatus;
use App\Domain\Video\Models\Video;
use App\Enums\UserRole;
use App\Filament\Resources\Videos\Pages\ListVideos;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class NotificationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_notifications(): void
    {
        $this->getJson('/api/notifications')->assertUnauthorized();
        $this->postJson('/api/notifications/read-all')->assertUnauthorized();
    }

    public function test_creator_is_notified_when_a_moderator_approves_their_video(): void
    {
        $moderator = User::factory()->create(['role' => UserRole::Moderator]);
        $creator = User::factory()->create(['role' => UserRole::Creator]);
        $video = Video::factory()->create([
            'creator_id' => $creator->id,
            'title' => 'Sotigui le film',
            'source_status' => VideoSourceStatus::Ready,
        ]);

        Livewire::actingAs($moderator)
            ->test(ListVideos::class)
            ->mountTableAction('approve', $video)
            ->callMountedTableAction();

        $this->assertSame(1, $creator->notifications()->count());

        $response = $this->actingAs($creator, 'sanctum')->getJson('/api/notifications')->assertOk();
        $response->assertJsonPath('unread_count', 1);
        $response->assertJsonPath('data.0.data.type', 'video_status_changed');
        $response->assertJsonPath('data.0.data.video_id', $video->id);
        $response->assertJsonPath('data.0.data.video_title', 'Sotigui le film');
        $response->assertJsonPath('data.0.data.status', 'approved');
        $response->assertJsonPath('data.0.read', false);
    }

    public function test_creator_is_notified_with_the_reason_when_a_moderator_rejects_their_video(): void
    {
        $moderator = User::factory()->create(['role' => UserRole::Moderator]);
        $creator = User::factory()->create(['role' => UserRole::Creator]);
        $video = Video::factory()->create(['creator_id' => $creator->id]);

        Livewire::actingAs($moderator)
            ->test(ListVideos::class)
            ->mountTableAction('reject', $video)
            ->setTableActionData(['rejection_reason' => 'Qualité vidéo insuffisante.'])
            ->callMountedTableAction();

        $response = $this->actingAs($creator, 'sanctum')->getJson('/api/notifications')->assertOk();
        $response->assertJsonPath('data.0.data.status', 'rejected');
        $response->assertJsonPath('data.0.data.rejection_reason', 'Qualité vidéo insuffisante.');
    }

    public function test_creator_is_notified_when_a_moderator_replies_but_not_for_their_own_messages(): void
    {
        $creator = User::factory()->create(['role' => UserRole::Creator]);
        $moderator = User::factory()->create(['role' => UserRole::Moderator]);

        app(SendMessage::class)($creator, $creator, "J'ai une question.");
        $this->assertSame(0, $creator->notifications()->count());

        app(SendMessage::class)($creator, $moderator, 'Voici la réponse.');
        $this->assertSame(1, $creator->notifications()->count());

        $response = $this->actingAs($creator, 'sanctum')->getJson('/api/notifications')->assertOk();
        $response->assertJsonPath('data.0.data.type', 'new_moderator_message');
        $response->assertJsonPath('data.0.data.excerpt', 'Voici la réponse.');
    }

    public function test_creator_can_mark_a_single_notification_as_read(): void
    {
        $creator = User::factory()->create(['role' => UserRole::Creator]);
        $video = Video::factory()->create(['creator_id' => $creator->id, 'source_status' => VideoSourceStatus::Ready]);
        $video->creator->notify(new \App\Notifications\VideoStatusChanged($video));

        $notificationId = $creator->notifications()->first()->id;

        $this->actingAs($creator, 'sanctum')
            ->postJson("/api/notifications/{$notificationId}/read")
            ->assertOk()
            ->assertJsonPath('read', true);

        $this->assertNotNull($creator->notifications()->first()->fresh()->read_at);
    }

    public function test_creator_can_mark_all_notifications_as_read(): void
    {
        $creator = User::factory()->create(['role' => UserRole::Creator]);
        $videoA = Video::factory()->create(['creator_id' => $creator->id]);
        $videoB = Video::factory()->create(['creator_id' => $creator->id]);
        $creator->notify(new \App\Notifications\VideoStatusChanged($videoA));
        $creator->notify(new \App\Notifications\VideoStatusChanged($videoB));

        $this->actingAs($creator, 'sanctum')
            ->postJson('/api/notifications/read-all')
            ->assertOk();

        $this->assertSame(0, $creator->unreadNotifications()->count());
    }

    public function test_a_user_cannot_mark_another_users_notification_as_read(): void
    {
        $creator = User::factory()->create(['role' => UserRole::Creator]);
        $otherViewer = User::factory()->create(['role' => UserRole::Viewer]);
        $video = Video::factory()->create(['creator_id' => $creator->id]);
        $creator->notify(new \App\Notifications\VideoStatusChanged($video));

        $notificationId = $creator->notifications()->first()->id;

        $this->actingAs($otherViewer, 'sanctum')
            ->postJson("/api/notifications/{$notificationId}/read")
            ->assertNotFound();

        $this->assertNull($creator->notifications()->first()->fresh()->read_at);
    }
}
