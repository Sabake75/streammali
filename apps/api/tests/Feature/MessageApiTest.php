<?php

namespace Tests\Feature;

use App\Domain\Moderation\Actions\SendMessage;
use App\Enums\UserRole;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MessageApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_creator_can_send_a_message_to_the_moderation_team(): void
    {
        $creator = User::factory()->create(['role' => UserRole::Creator]);

        $response = $this->actingAs($creator, 'sanctum')
            ->postJson('/api/messages', ['body' => 'Bonjour, une question sur ma vidéo.'])
            ->assertCreated();

        $response->assertJsonPath('body', 'Bonjour, une question sur ma vidéo.');
        $response->assertJsonPath('sender.role', 'creator');

        $this->assertDatabaseHas('messages', [
            'user_id' => $creator->id,
            'sender_id' => $creator->id,
            'body' => 'Bonjour, une question sur ma vidéo.',
        ]);
    }

    /**
     * The same support channel serves viewers too — no separate "customer
     * support" system, just the same one-thread-per-user model the creator
     * messaging already used.
     */
    public function test_a_viewer_can_send_a_message_to_the_moderation_team(): void
    {
        $viewer = User::factory()->create(['role' => UserRole::Viewer]);

        $response = $this->actingAs($viewer, 'sanctum')
            ->postJson('/api/messages', ['body' => "J'ai un problème avec un paiement."])
            ->assertCreated();

        $response->assertJsonPath('sender.role', 'viewer');

        $this->assertDatabaseHas('messages', [
            'user_id' => $viewer->id,
            'sender_id' => $viewer->id,
            'body' => "J'ai un problème avec un paiement.",
        ]);
    }

    public function test_a_creator_sees_their_own_messages_and_the_moderators_replies_in_order(): void
    {
        $creator = User::factory()->create(['role' => UserRole::Creator]);
        $moderator = User::factory()->create(['role' => UserRole::Moderator]);

        app(SendMessage::class)($creator, $creator, 'Première question.');
        app(SendMessage::class)($creator, $moderator, 'Réponse de la modération.');

        $response = $this->actingAs($creator, 'sanctum')
            ->getJson('/api/messages')
            ->assertOk();

        $response->assertJsonPath('data.0.body', 'Première question.');
        $response->assertJsonPath('data.0.sender.role', 'creator');
        $response->assertJsonPath('data.1.body', 'Réponse de la modération.');
        $response->assertJsonPath('data.1.sender.role', 'moderator');
    }

    public function test_a_creator_cannot_see_another_users_messages(): void
    {
        $creatorA = User::factory()->create(['role' => UserRole::Creator]);
        $creatorB = User::factory()->create(['role' => UserRole::Creator]);

        app(SendMessage::class)($creatorA, $creatorA, 'Message privé de A.');

        $response = $this->actingAs($creatorB, 'sanctum')
            ->getJson('/api/messages')
            ->assertOk();

        $response->assertJsonCount(0, 'data');
    }

    public function test_a_moderator_cannot_use_the_messaging_endpoints(): void
    {
        $moderator = User::factory()->create(['role' => UserRole::Moderator]);

        $this->actingAs($moderator, 'sanctum')
            ->postJson('/api/messages', ['body' => 'test'])
            ->assertForbidden();

        $this->actingAs($moderator, 'sanctum')
            ->getJson('/api/messages')
            ->assertForbidden();
    }

    public function test_a_moderator_can_reply_to_a_creator_from_the_back_office(): void
    {
        $moderator = User::factory()->create(['role' => UserRole::Moderator]);
        $creator = User::factory()->create(['role' => UserRole::Creator]);

        app(SendMessage::class)($creator, $creator, "J'ai une question.");

        Livewire::actingAs($moderator)
            ->test(ListUsers::class)
            ->mountTableAction('messagerie', $creator)
            ->setTableActionData(['reply' => 'Voici la réponse de la modération.'])
            ->callMountedTableAction();

        $this->assertDatabaseHas('messages', [
            'user_id' => $creator->id,
            'sender_id' => $moderator->id,
            'body' => 'Voici la réponse de la modération.',
        ]);
    }

    public function test_a_moderator_can_reply_to_a_viewer_from_the_back_office(): void
    {
        $moderator = User::factory()->create(['role' => UserRole::Moderator]);
        $viewer = User::factory()->create(['role' => UserRole::Viewer]);

        app(SendMessage::class)($viewer, $viewer, "Mon paiement n'est pas passé.");

        Livewire::actingAs($moderator)
            ->test(ListUsers::class)
            ->mountTableAction('messagerie', $viewer)
            ->setTableActionData(['reply' => 'On regarde ça.'])
            ->callMountedTableAction();

        $this->assertDatabaseHas('messages', [
            'user_id' => $viewer->id,
            'sender_id' => $moderator->id,
            'body' => 'On regarde ça.',
        ]);
    }
}
