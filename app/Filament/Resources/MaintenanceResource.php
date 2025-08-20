<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Vehicle;
use Filament\Forms\Get;
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

        $aceiteIds = MaintenanceItem::query()
            ->whereIn('name', ['ACEITE SINTETICO - MOTOR', 'ACEITE DE CAJA DE CAMBIOS'])
            ->pluck('id')
            ->map(fn($id) => (string) $id) // el Select devuelve string; igualamos tipos
            ->all();
        return $form
            ->schema([
                Forms\Components\Grid::make(4) // columna principal 2/3 y sidebar 1/3
                    ->schema([

                        // =========================
                        // 🔹 COLUMNA IZQUIERDA
                        // =========================
                        Forms\Components\Grid::make()
                            ->columnSpan(2)
                            ->schema([

                                Forms\Components\Section::make('Información Básica')
                                    ->schema([
                                        Forms\Components\Grid::make(2)->schema([
                                            Forms\Components\Select::make('vehicle_id')
                                                ->label('Vehículo')
                                                ->options(Vehicle::whereIn('status', ['Operativo', 'Recepción'])->pluck('placa', 'id')->toArray())
                                                ->required()
                                                ->searchable()
                                                ->native(false),

                                            Forms\Components\Select::make('maintenance_item_id')
                                                ->label('Tipo de Mantenimiento')
                                                ->options(MaintenanceItem::where('is_active', true)->pluck('name', 'id'))
                                                ->searchable()
                                                ->live()
                                                ->native(false),
                                        ]),

                                        Forms\Components\TextInput::make('mileage_at_service')
                                            ->label('Kilometraje actual')
                                            ->required()
                                            ->numeric()
                                            ->suffix(' km'),

                                        Forms\Components\MarkdownEditor::make('notes_valorization')
                                            ->label('Notas de Valoración')
                                            ->columnSpanFull(),
                                    ]),

                                Forms\Components\Section::make('Costos')
                                    ->schema([
                                        Forms\Components\Grid::make(2)->schema([
                                            Forms\Components\TextInput::make('labor_cost')
                                                ->label('Mano de Obra')
                                                ->required()
                                                ->numeric()
                                                ->prefix('S/.')
                                                ->reactive()
                                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                    $set(
                                                        'total_cost',
                                                        (float) ($get('labor_cost') ?? 0) +
                                                            (float) ($get('parts_cost') ?? 0) +
                                                            (float) ($get('extra_cost') ?? 0)
                                                    );
                                                }),

                                            Forms\Components\TextInput::make('parts_cost')
                                                ->label('Piezas')
                                                ->required()
                                                ->numeric()
                                                ->prefix('S/.')
                                                ->reactive()
                                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                    $set(
                                                        'total_cost',
                                                        (float) ($get('labor_cost') ?? 0) +
                                                            (float) ($get('parts_cost') ?? 0) +
                                                            (float) ($get('extra_cost') ?? 0)
                                                    );
                                                }),

                                            Forms\Components\TextInput::make('extra_cost')
                                                ->label('Extra')
                                                ->numeric()
                                                ->prefix('S/.')
                                                ->default(null)
                                                ->reactive()
                                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                    $set(
                                                        'total_cost',
                                                        (float) ($get('labor_cost') ?? 0) +
                                                            (float) ($get('parts_cost') ?? 0) +
                                                            (float) ($get('extra_cost') ?? 0)
                                                    );
                                                }),

                                            Forms\Components\TextInput::make('total_cost')
                                                ->label('Total')
                                                ->required()
                                                ->numeric()
                                                ->prefix('S/.')
                                                ->disabled()
                                                ->dehydrated(),
                                        ]),
                                    ]),


                            ]),

                        // =========================
                        // 🔹 COLUMNA DERECHA (SIDEBAR)
                        // =========================
                        Forms\Components\Grid::make()
                            ->columnSpan(2)
                            ->schema([

                                Forms\Components\Section::make('Estado')
                                    ->schema([
                                        Forms\Components\DatePicker::make('service_date')
                                            ->label('Fecha de Servicio')
                                            ->required()
                                            ->default(now())
                                            ->native(false),

                                        Forms\Components\TextInput::make('interval_km')
                                            ->label('Intervalo')
                                            ->numeric()
                                            ->suffix(' km'),
                                    ])->columns(2),

                                Forms\Components\Section::make('Frenos')
                                    //->collapsed()
                                    ->schema([
                                        Forms\Components\TextInput::make('front_left_brake_pad')
                                            ->label('Del. Izquierda')
                                            ->numeric()
                                            ->suffix('%'),

                                        Forms\Components\TextInput::make('front_right_brake_pad')
                                            ->label('Del. Derecha')
                                            ->numeric()
                                            ->suffix('%'),

                                        Forms\Components\TextInput::make('rear_left_brake_pad')
                                            ->label('Tras. Izquierda')
                                            ->numeric()
                                            ->suffix('%'),

                                        Forms\Components\TextInput::make('rear_right_brake_pad')
                                            ->label('Tras. Derecha')
                                            ->numeric()
                                            ->suffix('%'),
                                    ])->columns(2),

                                Forms\Components\Section::make('Aceite')
                                    // ->collapsed()
                                    ->schema([
                                        Forms\Components\TextInput::make('progres_bar')
                                            ->label('Progreso')
                                            ->required()
                                            ->minValue(0)
                                            ->maxValue(100)
                                            ->suffix('%')
                                            // requerido solo si aplica
                                            ->required(fn(Get $get) => in_array($get('maintenance_item_id'), $aceiteIds, true))
                                            // oculto cuando NO aplica
                                            ->hidden(fn(Get $get) => ! in_array($get('maintenance_item_id'), $aceiteIds, true))
                                            // solo se envía a la DB si aplica (evita errores de validación/required)
                                            ->dehydrated(fn(Get $get) => in_array($get('maintenance_item_id'), $aceiteIds, true))
                                            ->default(null)
                                    ]),
                                Forms\Components\Section::make('Imágenes y Archivos')
                                    //->collapsed()
                                    ->schema([
                                        Forms\Components\FileUpload::make('photo')
                                            ->label('Foto')
                                            ->image()
                                            ->directory('maintenance/photos'),

                                        Forms\Components\FileUpload::make('file')
                                            ->label('Archivo adicional')
                                            ->directory('maintenance/files'),
                                    ]),
                            ]),
                    ]),
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
            ->striped()
            ->paginated([5, 10, 25, 50, 100, 'all'])
            ->defaultPaginationPageOption(5)
            ->groups([
                Group::make('vehicle.placa')
                    ->label('Vehiculo')
                    ->collapsible(),
                Group::make('created_at')
                    ->label('Fecha de Creación')
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
                            fn($record) => is_numeric($record->{$field}) && $record->{$field} >= 70 ? 'success'
                                : (is_numeric($record->{$field}) && $record->{$field} >= 30 ? 'warning' : 'danger')
                        )
                        ->toggleable(isToggledHiddenByDefault: true);
                })->toArray(),

                ProgressColumn::make('front_left_brake_pad')
                    ->label('Pastilla Del. Izq.')
                    ->color(
                        fn($record) => is_numeric($record->front_left_brake_pad) && $record->front_left_brake_pad >= 70 ? 'success' : (is_numeric($record->front_left_brake_pad) && $record->front_left_brake_pad >= 30 ? 'warning' : 'danger')
                    )
                    ->toggleable(isToggledHiddenByDefault: true),
                ProgressColumn::make('front_right_brake_pad')
                    ->label('Pastilla Del. Der.')
                    ->color(
                        fn($record) => is_numeric($record->front_right_brake_pad) && $record->front_right_brake_pad >= 70 ? 'success' : (is_numeric($record->front_right_brake_pad) && $record->front_right_brake_pad >= 30 ? 'warning' : 'danger')
                    )
                    ->toggleable(isToggledHiddenByDefault: true),
                ProgressColumn::make('rear_left_brake_pad')
                    ->label('Pastilla Tra. Izq.')
                    ->color(
                        fn($record) => is_numeric($record->rear_left_brake_pad) && $record->rear_left_brake_pad >= 70 ? 'success' : (is_numeric($record->rear_left_brake_pad) && $record->rear_left_brake_pad >= 30 ? 'warning' : 'danger')
                    )
                    ->toggleable(isToggledHiddenByDefault: true),
                ProgressColumn::make('rear_right_brake_pad')
                    ->label('Pastilla Tra. Der.')
                    ->color(
                        fn($record) => is_numeric($record->rear_right_brake_pad) && $record->rear_right_brake_pad >= 70 ? 'success' : (is_numeric($record->rear_right_brake_pad) && $record->rear_right_brake_pad >= 30 ? 'warning' : 'danger')
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
