<?php

namespace App\Filament\Resources\MaintenanceItemResource\Pages;

use App\Filament\Resources\MaintenanceItemResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMaintenanceItems extends ListRecords
{
    protected static string $resource = MaintenanceItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
