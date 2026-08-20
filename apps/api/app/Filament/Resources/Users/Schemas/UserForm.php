<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Domain\Moderation\Enums\AccountStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nom')
                    ->required()
                    ->maxLength(255),
                TextInput::make('phone')
                    ->label('Téléphone')
                    ->maxLength(32),
                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->maxLength(255),
                Select::make('account_status')
                    ->label('Statut')
                    ->options(collect(AccountStatus::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()]))
                    ->required(),
                Textarea::make('account_status_reason')
                    ->label('Motif (suspension/blocage)')
                    ->columnSpanFull(),
                DateTimePicker::make('identity_verified_at')
                    ->label('Identité vérifiée le'),
            ]);
    }
}
