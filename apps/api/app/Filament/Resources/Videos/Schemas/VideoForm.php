<?php

namespace App\Filament\Resources\Videos\Schemas;

use App\Domain\Moderation\Enums\VideoStatus;
use App\Domain\Video\Enums\VideoCategory;
use App\Enums\UserRole;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class VideoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('creator_id')
                    ->label('Créateur')
                    ->relationship(
                        name: 'creator',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn ($query) => $query->where('role', UserRole::Creator),
                    )
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('title')
                    ->label('Titre')
                    ->required()
                    ->maxLength(255),
                Textarea::make('description')
                    ->label('Description')
                    ->columnSpanFull(),
                Select::make('category')
                    ->label('Catégorie')
                    ->options(collect(VideoCategory::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()]))
                    ->required(),
                TextInput::make('poster_path')
                    ->label('URL de la jaquette'),
                TextInput::make('duration_seconds')
                    ->label('Durée (secondes)')
                    ->numeric(),
                TextInput::make('price')
                    ->label('Prix (FCFA)')
                    ->numeric()
                    ->default(25)
                    ->required(),
                Select::make('status')
                    ->label('Statut de modération')
                    ->options(collect(VideoStatus::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()]))
                    ->default(VideoStatus::Pending)
                    ->required()
                    ->live(),
                Textarea::make('rejection_reason')
                    ->label('Motif du refus')
                    ->visible(fn ($get) => $get('status') === VideoStatus::Rejected->value)
                    ->required(fn ($get) => $get('status') === VideoStatus::Rejected->value)
                    ->columnSpanFull(),
            ]);
    }
}
