<?php

namespace App\Domain\Moderation\Actions;

use App\Domain\Moderation\Models\Report;
use App\Domain\Video\Models\Video;
use App\Models\User;

class ReportVideo
{
    public function __invoke(Video $video, User $reporter, string $reason): Report
    {
        return Report::create([
            'video_id' => $video->id,
            'reporter_id' => $reporter->id,
            'reason' => $reason,
        ]);
    }
}
