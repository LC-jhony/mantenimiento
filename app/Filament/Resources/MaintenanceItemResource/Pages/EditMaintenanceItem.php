<?php

namespace App\Filament\Resources\MaintenanceItemResource\Pages;

use App\Filament\Resources\MaintenanceItemResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMaintenanceItem extends EditRecord
{
    protected static string $resource = MaintenanceItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
