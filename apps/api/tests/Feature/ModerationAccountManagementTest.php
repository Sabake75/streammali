<?php

namespace Tests\Feature;

use App\Domain\Moderation\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ModerationAccountManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_moderator_can_view_the_account_management_list(): void
    {
        $moderator = User::factory()->create(['role' => UserRole::Moderator]);
        User::factory()->create(['role' => UserRole::Viewer, 'name' => 'Awa Traoré']);

        $this->actingAs($moderator)
            ->get('/moderation/users')
            ->assertOk()
            ->assertSee('Awa Traoré');
    }

    public function test_moderator_accounts_are_not_listed_in_account_management(): void
    {
        $moderator = User::factory()->create(['role' => UserRole::Moderator]);
        $otherModerator = User::factory()->create(['role' => UserRole::Moderator, 'name' => 'Autre Modérateur']);

        $this->actingAs($moderator)
            ->get('/moderation/users')
            ->assertOk()
            ->assertDontSee('Autre Modérateur');
    }

    public function test_suspended_account_cannot_log_in(): void
    {
        $viewer = User::factory()->create([
            'role' => UserRole::Viewer,
            'phone' => '+223 76 00 00 00',
            'password' => '1234',
            'account_status' => AccountStatus::Suspended,
        ]);

        $this->postJson('/api/login', [
            'phone' => '+223 76 00 00 00',
            'password' => '1234',
        ])->assertStatus(422);
    }

    public function test_blocked_account_with_an_existing_token_is_rejected_by_the_active_account_middleware(): void
    {
        $viewer = User::factory()->create(['role' => UserRole::Viewer]);
        $token = $viewer->createToken('api')->plainTextToken;

        $viewer->update(['account_status' => AccountStatus::Blocked]);

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/user')
            ->assertForbidden();
    }

    public function test_active_account_with_a_token_is_not_affected_by_the_active_account_middleware(): void
    {
        $viewer = User::factory()->create(['role' => UserRole::Viewer]);
        $token = $viewer->createToken('api')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/user')
            ->assertOk();
    }

    public function test_moderator_can_reset_a_forgotten_pin(): void
    {
        $moderator = User::factory()->create(['role' => UserRole::Moderator]);
        $viewer = User::factory()->create([
            'role' => UserRole::Viewer,
            'phone' => '+223 76 00 00 00',
            'password' => '1234',
        ]);

        Livewire::actingAs($moderator)
            ->test(ListUsers::class)
            ->mountTableAction('reset_pin', $viewer)
            ->setTableActionData(['new_pin' => '5678'])
            ->callMountedTableAction();

        $this->postJson('/api/login', [
            'phone' => '+223 76 00 00 00',
            'password' => '1234',
        ])->assertStatus(422);

        $this->postJson('/api/login', [
            'phone' => '+223 76 00 00 00',
            'password' => '5678',
        ])->assertOk();
    }

    public function test_resetting_a_pin_revokes_the_accounts_existing_tokens(): void
    {
        $moderator = User::factory()->create(['role' => UserRole::Moderator]);
        $viewer = User::factory()->create(['role' => UserRole::Viewer]);
        $viewer->createToken('api');

        $this->assertSame(1, $viewer->tokens()->count());

        // Checked against DB state directly rather than round-tripping a
        // revoked token through an actual HTTP request: Livewire::actingAs()
        // leaves the web guard's session authenticated for the rest of the
        // test, which — depending on Sanctum's stateful-domain fallback —
        // can make a later `getJson()` call resolve the moderator's session
        // instead of cleanly rejecting the dead token, a test-harness
        // artifact rather than a real security gap.
        Livewire::actingAs($moderator)
            ->test(ListUsers::class)
            ->mountTableAction('reset_pin', $viewer)
            ->setTableActionData(['new_pin' => '5678'])
            ->callMountedTableAction();

        $this->assertSame(0, $viewer->fresh()->tokens()->count());
    }
}
