<?php

namespace App\Filament\Resources\Users\Tables;

use App\Domain\Moderation\Actions\SendMessage;
use App\Domain\Moderation\Enums\AccountStatus;
use App\Domain\Moderation\Models\Message;
use App\Enums\UserRole;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            // Par défaut Filament ouvre la page d'édition au clic sur
            // n'importe quelle cellule sans ->action()/->url() propre — un
            // modérateur cliquant sur le nom, le téléphone ou l'icône
            // "Identité vérifiée" atterrissait sur le formulaire d'édition
            // au lieu de rien faire. Même bug, même correctif que
            // VideosTable (voir Domain\Video\README) — jamais appliqué ici.
            ->recordUrl(null)
            ->columns([
                TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('phone')
                    ->label('Téléphone')
                    ->searchable(),
                TextColumn::make('role')
                    ->label('Rôle')
                    ->badge()
                    ->formatStateUsing(fn (UserRole $state) => match ($state) {
                        UserRole::Creator => 'Créateur',
                        UserRole::Viewer => 'Viewer',
                        UserRole::Moderator => 'Modérateur',
                    }),
                TextColumn::make('account_status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (AccountStatus $state) => $state->color())
                    ->formatStateUsing(fn (AccountStatus $state) => $state->label()),
                IconColumn::make('identity_verified_at')
                    ->label('Identité vérifiée')
                    ->boolean()
                    ->getStateUsing(fn ($record) => $record->identity_verified_at !== null),
                TextColumn::make('created_at')
                    ->label('Inscrit le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->label('Rôle')
                    ->options([
                        UserRole::Creator->value => 'Créateur',
                        UserRole::Viewer->value => 'Viewer',
                    ]),
                SelectFilter::make('account_status')
                    ->label('Statut')
                    ->options(collect(AccountStatus::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()])),
                TernaryFilter::make('identity_verified')
                    ->label('Identité vérifiée')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('identity_verified_at'),
                        false: fn ($query) => $query->whereNull('identity_verified_at'),
                    ),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('view_identity_document')
                        ->label("Pièce d'identité")
                        ->icon('heroicon-o-document-text')
                        ->color('gray')
                        ->visible(fn ($record) => $record->identity_document_path !== null)
                        ->url(fn ($record) => route('creators.identity-document', $record))
                        ->openUrlInNewTab(),
                    Action::make('suspend')
                        ->label('Suspendre')
                        ->icon('heroicon-o-pause-circle')
                        ->color('warning')
                        ->visible(fn ($record) => $record->account_status !== AccountStatus::Suspended)
                        ->schema([
                            Textarea::make('reason')
                                ->label('Motif')
                                ->required(),
                        ])
                        ->action(fn ($record, array $data) => $record->update([
                            'account_status' => AccountStatus::Suspended,
                            'account_status_reason' => $data['reason'],
                        ])),
                    Action::make('block')
                        ->label('Bloquer')
                        ->icon('heroicon-o-no-symbol')
                        ->color('danger')
                        ->visible(fn ($record) => $record->account_status !== AccountStatus::Blocked)
                        ->requiresConfirmation()
                        ->schema([
                            Textarea::make('reason')
                                ->label('Motif')
                                ->required(),
                        ])
                        ->action(fn ($record, array $data) => $record->update([
                            'account_status' => AccountStatus::Blocked,
                            'account_status_reason' => $data['reason'],
                        ])),
                    Action::make('reactivate')
                        ->label('Réactiver')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn ($record) => $record->account_status !== AccountStatus::Active)
                        ->requiresConfirmation()
                        ->action(fn ($record) => $record->update([
                            'account_status' => AccountStatus::Active,
                            'account_status_reason' => null,
                        ])),
                    Action::make('messagerie')
                        ->label('Messagerie')
                        ->icon('heroicon-o-chat-bubble-left-right')
                        ->color('gray')
                        ->visible(fn ($record) => $record->role === UserRole::Creator)
                        ->schema(fn ($record) => [
                            TextEntry::make('thread')
                                ->hiddenLabel()
                                ->state(fn () => static::formatThread($record))
                                ->html(),
                            Textarea::make('reply')
                                ->label('Répondre')
                                ->required()
                                ->rows(3),
                        ])
                        ->modalSubmitActionLabel('Envoyer')
                        ->action(fn ($record, array $data) => app(SendMessage::class)($record, auth()->user(), $data['reply'])),
                    Action::make('reset_pin')
                        ->label('Réinitialiser le code')
                        ->icon('heroicon-o-key')
                        ->color('gray')
                        ->visible(fn ($record) => $record->role !== UserRole::Moderator)
                        // Aucune récupération en libre-service n'existe encore
                        // (pas d'envoi de SMS) — c'est le seul chemin pour
                        // débloquer un compte dont le code à 4 chiffres est
                        // oublié. Révoque les jetons existants : si le compte a
                        // été compromis plutôt que simplement oublié, une
                        // session déjà ouverte ne doit pas survivre au reset.
                        ->requiresConfirmation()
                        ->schema([
                            TextInput::make('new_pin')
                                ->label('Nouveau code (4 chiffres)')
                                ->required()
                                ->rule('digits:4'),
                        ])
                        ->action(function ($record, array $data) {
                            $record->update(['password' => $data['new_pin']]);
                            $record->tokens()->delete();
                        }),
                    Action::make('verify_identity')
                        ->label(fn ($record) => $record->identity_verified_at ? 'Annuler la vérification' : "Vérifier l'identité")
                        ->icon('heroicon-o-identification')
                        ->color('gray')
                        ->action(fn ($record) => $record->update([
                            'identity_verified_at' => $record->identity_verified_at ? null : now(),
                        ])),
                    EditAction::make(),
                ]),
            ]);
        // Pas de toolbarActions/DeleteBulkAction : même risque de cascade
        // qu'un DELETE individuel (voir EditUser::getHeaderActions et
        // Domain\Video\README pour le précédent sur les vidéos), en pire —
        // une sélection multiple par erreur détruirait plusieurs comptes
        // d'un coup.
    }

    private static function formatThread(User $creator): string
    {
        $messages = $creator->messages()->with('sender')->oldest()->get();

        if ($messages->isEmpty()) {
            return '<em>Aucun message pour l\'instant.</em>';
        }

        return $messages
            ->map(function (Message $message) {
                $author = $message->sender->role === UserRole::Moderator
                    ? 'Modération'
                    : e($message->sender->name);
                $when = $message->created_at->format('d/m/Y H:i');
                $body = nl2br(e($message->body));

                return "<strong>{$author}</strong> <span style=\"color:#888\">({$when})</span><br>{$body}";
            })
            ->implode('<hr style="margin:8px 0">');
    }
}
