<?php

namespace App\Filament\Resources\MaintenanceResource\Pages;

use App\Filament\Resources\MaintenanceResource;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\ListRecords;

class ListMaintenances extends ListRecords
{
    protected static string $resource = MaintenanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('print')
                ->label('Imprimir')
                ->modalHeading('Imprimir Mantenimiento')
                ->color('info')
                ->icon('heroicon-o-printer')
                ->form([
                    Forms\Components\Grid::make(3)
                        ->schema([
                            Forms\Components\Select::make('month')
                                ->label('Mes')
                                ->options([
                                    1 => 'Enero',
                                    2 => 'Febrero',
                                    3 => 'Marzo',
                                    4 => 'Abril',
                                    5 => 'Mayo',
                                    6 => 'Junio',
                                    7 => 'Julio',
                                    8 => 'Agosto',
                                    9 => 'Septiembre',
                                    10 => 'Octubre',
                                    11 => 'Noviembre',
                                    12 => 'Diciembre',
                                ])
                                ->required()
                                ->native(false),
                            Forms\Components\DatePicker::make('start_date')
                                ->label('Fecha Inicio')
                                ->required()
                                ->native(false),
                            Forms\Components\DatePicker::make('end_date')
                                ->label('Fecha final')
                                ->required()
                                ->native(false),
                        ])
                ])
                ->modalSubmitActionLabel('Generar PDF')
                ->action(function (array $data) {
                    return redirect()->route('print-maintenance-vehicle', [
                        'month' => $data['month'] ?? null,
                        'start_date' => $data['start_date'] ?? null,
                        'end_date' => $data['end_date'] ?? null,
                    ]);
                }),
            Actions\CreateAction::make(),
        ];
    }
}
