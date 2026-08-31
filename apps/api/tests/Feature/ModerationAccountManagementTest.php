<?php

namespace Tests\Feature;

use App\Domain\Moderation\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Tables\UsersTable;
use App\Models\User;
use Filament\Tables\Table;
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

    public function test_the_account_list_has_no_bulk_delete_action(): void
    {
        // A real DELETE on `users` cascades to videos/payments/payouts/
        // ledger_entries/messages/reviews/favorites/reports — an accidental
        // multi-select "delete" would destroy real transaction history.
        // Suspend/block are the intended moderation tools.
        $moderator = User::factory()->create(['role' => UserRole::Moderator]);

        Livewire::actingAs($moderator)
            ->test(ListUsers::class)
            ->assertTableBulkActionDoesNotExist('delete');
    }

    public function test_the_edit_account_page_has_no_delete_action(): void
    {
        $moderator = User::factory()->create(['role' => UserRole::Moderator]);
        $viewer = User::factory()->create(['role' => UserRole::Viewer]);

        Livewire::actingAs($moderator)
            ->test(EditUser::class, ['record' => $viewer->getKey()])
            ->assertActionDoesNotExist('delete');
    }

    public function test_the_account_list_disables_the_default_click_to_edit_behavior(): void
    {
        // Filament opens the edit page by default when a cell has no
        // ->action()/->url() of its own — a moderator clicking the name,
        // phone, or "Identité vérifiée" icon landed on the edit form
        // instead of doing nothing. Same bug/fix already applied to
        // VideosTable (see Domain\Video\README), never applied here.
        $table = UsersTable::configure(new Table(new ListUsers));
        $viewer = User::factory()->create(['role' => UserRole::Viewer]);

        $this->assertNull($table->getRecordUrl($viewer));
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
