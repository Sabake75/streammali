<?php

namespace App\Filament\Resources\Payouts\Pages;

use App\Filament\Resources\Payouts\PayoutResource;
use Filament\Resources\Pages\EditRecord;

class EditPayout extends EditRecord
{
    protected static string $resource = PayoutResource::class;

    // Pas de DeleteAction : voir PayoutsTable.
    protected function getHeaderActions(): array
    {
        return [];
    }
}
