<?php

namespace App\Filament\Resources\DriverResource\Pages;

use Filament\Actions;
use App\Imports\DriverImport;
use pxlrbt\FilamentExcel\Columns\Column;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\DriverResource;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use pxlrbt\FilamentExcel\Actions\Pages\ExportAction;


class ListDrivers extends ListRecords
{
    protected static string $resource = DriverResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ExportAction::make()
                ->label('Exportar a Excel')
                ->color('success')
                ->exports([
                    ExcelExport::make('table')
                        ->withColumns([
                            Column::make('last_paternal_name')
                                ->heading('APELLIDO PATERNO'),
                            Column::make('last_maternal_name')
                                ->heading('APELLIDO MATERNO'),
                            Column::make('name')
                                ->heading('NOMBRES'),
                            Column::make('dni')
                                ->heading('DNI'),
                            Column::make('cargo.name')
                                ->heading('CARGO'),
                        ])
                        ->withFilename(date('Y-m-d') . ' - Empleados'),
                ]),
            \EightyNine\ExcelImport\ExcelImportAction::make()
                ->label('Importar')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('info')
                ->outlined()
                ->use(DriverImport::class)
                ->modalHeading('Importar Conductores')
                ->modalDescription('Seleccione un archivo Excel para importar los conductores.')
                ->modalFooterActionsAlignment('right')

                ->slideOver(),
            Actions\CreateAction::make(),
        ];
    }
}
