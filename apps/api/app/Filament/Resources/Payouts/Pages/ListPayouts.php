<?php

namespace App\Filament\Resources\Payouts\Pages;

use App\Filament\Resources\Payouts\PayoutResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPayouts extends ListRecords
{
    protected static string $resource = PayoutResource::class;

    // Filament title-cases each word of pluralModelLabel by default
    // ("Demandes De Retrait") — set explicitly to keep proper French
    // sentence case, same reasoning as $navigationLabel on the resource.
    protected static ?string $title = 'Demandes de retrait';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
