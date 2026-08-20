<?php

namespace Database\Factories;

use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Payment\Models\Payment;
use App\Domain\Video\Models\Video;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'buyer_id' => User::factory()->state(['role' => UserRole::Viewer]),
            'video_id' => Video::factory(),
            'amount' => 25,
            'provider' => 'orange_money',
            'payer_msisdn' => fake()->numerify('+223 7# ## ## ##'),
            'order_reference' => (string) Str::uuid(),
            'status' => PaymentStatus::Pending,
        ];
    }

    public function succeeded(): static
    {
        return $this->state(fn () => [
            'status' => PaymentStatus::Succeeded,
            'confirmed_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn () => [
            'status' => PaymentStatus::Failed,
            'confirmed_at' => now(),
        ]);
    }
}
