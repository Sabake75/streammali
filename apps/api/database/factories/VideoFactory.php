<?php

namespace Database\Factories;

use App\Domain\Moderation\Enums\VideoStatus;
use App\Domain\Video\Enums\VideoCategory;
use App\Domain\Video\Models\Video;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Video>
 */
class VideoFactory extends Factory
{
    protected $model = Video::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'creator_id' => User::factory()->state(['role' => \App\Enums\UserRole::Creator]),
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'category' => fake()->randomElement(VideoCategory::cases()),
            'duration_seconds' => fake()->numberBetween(60, 7200),
            'price' => 25,
            'status' => VideoStatus::Pending,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => VideoStatus::Approved,
            'rejection_reason' => null,
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => [
            'status' => VideoStatus::Rejected,
            'rejection_reason' => fake()->sentence(),
        ]);
    }
}
