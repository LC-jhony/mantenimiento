<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Vehicle;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\Maintenance;
use App\Models\MaintenanceItem;
use Filament\Resources\Resource;
use Filament\Tables\Grouping\Group;
use App\Tables\Columns\ProgressBarColumn;
use App\Tables\Columns\ProgressBrakeColumn;
use Filament\Tables\Columns\Summarizers\Sum;
use App\Filament\Resources\MaintenanceResource\Pages;
use RyanChandler\FilamentProgressColumn\ProgressColumn;

class MaintenanceResource extends Resource
{
    protected static ?string $model = Maintenance::class;

    protected static ?string $navigationIcon = 'untitledui-tool-02';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationGroup = 'Mantenimiento Vehicular';

    protected static ?string $modelLabel = 'Mantenimiento';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('vehicle_id')
                    ->label('Vehiculo')
                    ->options(Vehicle::whereIn('status', ['Operativo', 'Recepción'])->pluck('placa', 'id')->toArray())
                    ->required()
                    ->searchable('placa')

                    ->native(false),
                Forms\Components\Select::make('maintenance_item_id')
                    ->label('Mantenimiento Item')
                    ->options(
                        MaintenanceItem::where('is_active', true)->pluck('name', 'id')->toArray()
                    )
                    ->live()
                    ->searchable()
                    ->required()
                    ->native(false),
                Forms\Components\TextInput::make('mileage_at_service')
                    ->label('Kilometraje')
                    ->required()
                    ->maxLength(255),
                Forms\Components\DatePicker::make('service_date')
                    ->label('Fecha de Servicio')
                    ->required()
                    ->default(now()),
                Forms\Components\TextInput::make('labor_cost')
                    ->label('Costo de Mano de Obra')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('parts_cost')
                    ->label('Costo de Piezas')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('extra_cost')
                    ->label('Costo Extra')
                    ->numeric()
                    ->default(null),
                Forms\Components\TextInput::make('total_cost')
                    ->label('Costo Total')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('front_left_brake_pad')
                    ->label('Pastilla de Freno Delantera Izquierda')
                    ->numeric()
                    ->default(null),
                Forms\Components\TextInput::make('front_right_brake_pad')
                    ->label('Pastilla de Freno Delantera Derecha')
                    ->numeric()
                    ->default(null),
                Forms\Components\TextInput::make('rear_left_brake_pad')
                    ->label('Pastilla de Freno Trasera Izquierda')
                    ->numeric()
                    ->default(null),
                Forms\Components\TextInput::make('rear_right_brake_pad')
                    ->label('Pastilla de Freno Trasera Derecha')
                    ->numeric()
                    ->default(null),
                Forms\Components\TextInput::make('progres_bar')
                    ->label('Progreso')
                    ->required()
                    ->maxLength(255)
                    ->live()
                    ->visible(fn($get) => in_array(
                        $get('maintenance_item_id'),
                        MaintenanceItem::whereIn('name', [
                            'ACEITE SINTETICO - MOTOR',
                            'ACEITE DE CAJA DE CAMBIOS',
                        ])->pluck('id')->toArray()
                    )),
                Forms\Components\MarkdownEditor::make('notes_valorization')
                    ->label('Notas de Valoración')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('photo')
                    ->label('Foto')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\TextInput::make('file')
                    ->label('Archivo')
                    ->maxLength(255)
                    ->default(null),
            ]);
    }

    public static function table(Table $table): Table
    {
        $brakePads = [
            'front_left_brake_pad' => 'Pastilla Del. Izq.',
            'front_right_brake_pad' => 'Pastilla Del. Der.',
            'rear_left_brake_pad' => 'Pastilla Tra. Izq.',
            'rear_right_brake_pad' => 'Pastilla Tra. Der.',
        ];
        return $table
            ->groups([
                Group::make('vehicle.placa')
                    ->label('Vehiculo')
                    ->collapsible(),
                Group::make('created_at')
                    ->date(),
            ])
            ->defaultGroup('vehicle.placa')
            ->striped()
            ->paginated([5, 10, 25, 50, 100, 'all'])
            ->defaultPaginationPageOption(5)
            ->columns([
                Tables\Columns\TextColumn::make('vehicle.placa')
                    ->label('Placa')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('maintenanceItem.name')
                    ->label('Mantenimiento')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('mileage_at_service')
                    ->label('Kilometraje')
                    ->searchable()
                    ->suffix(' km'),
                Tables\Columns\TextColumn::make('service_date')
                    ->label('Fecha de Servicio')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('labor_cost')
                    ->label('Costo de Mano de Obra')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('parts_cost')
                    ->label('Costo de Piezas')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('extra_cost')
                    ->label('Costo Extra')
                    ->placeholder('N/A')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('total_cost')
                    ->label(' Total')
                    ->numeric()
                    ->sortable()
                    ->summarize(Sum::make()
                        ->label('Costo Total')),

                ProgressBarColumn::make('progres_bar')
                    ->label('Estado de Aceite'),
                ProgressBrakeColumn::make('pastillas_frenos')
                    ->label('Pastillas de Freno')
                    ->progress(function ($record) use ($brakePads) {
                        $values = [];
                        foreach ($brakePads as $field => $label) {
                            $val = $record->{$field};
                            if (is_numeric($val)) {
                                $values[] = $val;
                            }
                        }
                        return count($values) ? array_sum($values) / count($values) : null;
                    })
                    ->sortable(),

                ...collect($brakePads)->map(function ($label, $field) {
                    return ProgressColumn::make($field)
                        ->label($label)
                        ->color(
                            fn($record) =>
                            is_numeric($record->{$field}) && $record->{$field} >= 70 ? 'success'
                                : (is_numeric($record->{$field}) && $record->{$field} >= 30 ? 'warning' : 'danger')
                        )
                        ->toggleable(isToggledHiddenByDefault: true);
                })->toArray(),

                ProgressColumn::make('front_left_brake_pad')
                    ->label('Pastilla Del. Izq.')
                    ->color(
                        fn($record) =>
                        is_numeric($record->front_left_brake_pad) && $record->front_left_brake_pad >= 70 ? 'success' : (is_numeric($record->front_left_brake_pad) && $record->front_left_brake_pad >= 30 ? 'warning' : 'danger')
                    )
                    ->toggleable(isToggledHiddenByDefault: true),
                ProgressColumn::make('front_right_brake_pad')
                    ->label('Pastilla Del. Der.')
                    ->color(
                        fn($record) =>
                        is_numeric($record->front_right_brake_pad) && $record->front_right_brake_pad >= 70 ? 'success' : (is_numeric($record->front_right_brake_pad) && $record->front_right_brake_pad >= 30 ? 'warning' : 'danger')
                    )
                    ->toggleable(isToggledHiddenByDefault: true),
                ProgressColumn::make('rear_left_brake_pad')
                    ->label('Pastilla Tra. Izq.')
                    ->color(
                        fn($record) =>
                        is_numeric($record->rear_left_brake_pad) && $record->rear_left_brake_pad >= 70 ? 'success' : (is_numeric($record->rear_left_brake_pad) && $record->rear_left_brake_pad >= 30 ? 'warning' : 'danger')
                    )
                    ->toggleable(isToggledHiddenByDefault: true),
                ProgressColumn::make('rear_right_brake_pad')
                    ->label('Pastilla Tra. Der.')
                    ->color(
                        fn($record) =>
                        is_numeric($record->rear_right_brake_pad) && $record->rear_right_brake_pad >= 70 ? 'success' : (is_numeric($record->rear_right_brake_pad) && $record->rear_right_brake_pad >= 30 ? 'warning' : 'danger')
                    )
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])

            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMaintenances::route('/'),
            'create' => Pages\CreateMaintenance::route('/create'),
            'edit' => Pages\EditMaintenance::route('/{record}/edit'),
        ];
    }
}
