<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Vehicle;
use Filament\Forms\Form;
use App\Enum\VeicleStatus;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\VehicleResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\VehicleResource\RelationManagers;

class VehicleResource extends Resource
{
    protected static ?string $model = Vehicle::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationGroup = 'Mantenimiento Vehicular';
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
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
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
                    ->color(fn(string $state): string => match ($state) {
                        'Operativo' => 'success',
                        'En Mantenimiento' => 'warning',
                        'Fuera de Servicio' => 'danger',
                        'En Reparación' => 'gray',
                        'Recepción' => 'info',

                        default => 'primary',
                    }),
                Tables\Columns\TextColumn::make('current_mileage')
                    ->label('Kilometraje Actual')
                    ->suffix(' km')
                    ->sortable(),
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
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListVehicles::route('/'),
            'create' => Pages\CreateVehicle::route('/create'),
            'view' => Pages\ViewVehicle::route('/{record}'),
            'edit' => Pages\EditVehicle::route('/{record}/edit'),
        ];
    }
}
