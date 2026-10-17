<?php

namespace Tests\Feature;

use App\Domain\Payment\Models\Payment;
use App\Domain\Video\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurgeVideosTest extends TestCase
{
    use RefreshDatabase;

    public function test_dry_run_reports_without_deleting_anything(): void
    {
        $video = Video::factory()->create();

        $this->artisan('videos:purge', ['ids' => [$video->id]])->assertSuccessful();

        $this->assertSame(1, Video::count());
    }

    public function test_force_deletes_a_video_with_no_payments(): void
    {
        $video = Video::factory()->create();

        $this->artisan('videos:purge', ['ids' => [$video->id], '--force' => true])->assertSuccessful();

        $this->assertSame(0, Video::count());
    }

    public function test_force_refuses_to_delete_a_video_with_a_payment(): void
    {
        $video = Video::factory()->create();
        Payment::factory()->create(['video_id' => $video->id]);

        $this->artisan('videos:purge', ['ids' => [$video->id], '--force' => true])->assertSuccessful();

        $this->assertSame(1, Video::count());
    }

    public function test_an_unknown_id_is_skipped_without_failing(): void
    {
        $this->artisan('videos:purge', ['ids' => [999999], '--force' => true])->assertSuccessful();
    }
}
