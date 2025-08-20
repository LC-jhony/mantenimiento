<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Vehicle;
use Filament\Forms\Form;
use App\Enum\VeicleStatus;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Tables\Actions\ActionGroup;
use App\Tables\Columns\ProgressBarColumn;
use Filament\Support\Enums\VerticalAlignment;
use App\Filament\Resources\VehicleResource\Pages;
use Illuminate\Contracts\Database\Eloquent\Builder;
use RyanChandler\FilamentProgressColumn\ProgressColumn;

class VehicleResource extends Resource
{
    protected static ?string $model = Vehicle::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationGroup = 'Mantenimiento Vehicular';

    protected static ?string $modelLabel = 'Vehículo';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Registro de Vehículo')
                    ->description('Ingrese los datos del vehículo')
                    ->icon('heroicon-o-rectangle-stack')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Grid::make()
                            ->columns(3)
                            ->schema([
                                Forms\Components\TextInput::make('code')
                                    ->label('PROG.')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('placa')
                                    ->label('Placa')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('marca')
                                    ->label('Marca')
                                    ->required()
                                    ->maxLength(255),
                            ]),
                        Forms\Components\Grid::make()
                            ->columns(4)
                            ->schema([
                                Forms\Components\TextInput::make('unidad')
                                    ->label('Unidad')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('property_card')
                                    ->label('Tarjeta de Propiedad')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\Select::make('status')
                                    ->label('Estado del Vehículo')
                                    ->options(VeicleStatus::class)
                                    ->required()
                                    ->native(false),
                                Forms\Components\TextInput::make('current_mileage')
                                    ->required()
                                    ->helperText(str('Registro del **Kilometraje** actual del Vehiculo')->inlineMarkdown()->toHtmlString()),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table

            ->striped()
            ->paginated([5, 10, 25, 50, 100, 'all'])
            ->defaultPaginationPageOption(5)

            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('PROG.')
                    ->searchable(),
                Tables\Columns\TextColumn::make('placa')
                    ->label('Placa')
                    ->searchable(),
                Tables\Columns\TextColumn::make('marca')
                    ->label('Marca')
                    ->searchable(),
                Tables\Columns\TextColumn::make('unidad')
                    ->label('Unidad')
                    ->searchable(),
                Tables\Columns\TextColumn::make('property_card')
                    ->label('Tarjeta de Propiedad')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado del Vehículo')
                    ->badge()
                    ->alignCenter()
                    ->color(fn(string $state): string => match ($state) {
                        'Operativo' => 'success',
                        'En Mantenimiento' => 'warning',
                        'Fuera de Servicio' => 'danger',
                        'En Reparación' => 'gray',
                        'Recepción' => 'info',

                        default => 'primary',
                    }),

                // 🔹 Barra de progreso Aceite
                ProgressBarColumn::make('oil_progress')
                    ->label('Estado de Aceite'),

                // 🔹 Barra de progreso Frenos
                ProgressBarColumn::make('brake_progress')
                    ->label('Frenos'),
                Tables\Columns\TextColumn::make('current_mileage')
                    ->label('Kilometraje Actual')
                    ->suffix(' km')
                    ->badge()
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha de Registro')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Fecha de Actualización')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ])->label('Acciones'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
    // public static function getTableQuery(): Builder
    // {
    //     return parent::getTableQuery()->with(['lastMaintenance', 'lastMaintenanceMileage']);
    // }
    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVehicles::route('/'),
            'create' => Pages\CreateVehicle::route('/create'),
            'view' => Pages\ViewVehicle::route('/{record}'),
            'edit' => Pages\EditVehicle::route('/{record}/edit'),
        ];
    }
}
