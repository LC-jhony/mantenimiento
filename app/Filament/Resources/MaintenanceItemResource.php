<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MaintenanceItemResource\Pages;
use App\Models\MaintenanceItem;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MaintenanceItemResource extends Resource
{
    protected static ?string $model = MaintenanceItem::class;

    protected static ?string $navigationIcon = 'css-list-tree';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationGroup = 'Mantenimiento Vehicular';

    protected static ?string $modelLabel = 'Ítem de Mantenimiento';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Registrar el Mantenimiento')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Radio::make('is_active')
                            ->label('Estado')
                            ->options([
                                '1' => 'Activo',
                                '0' => 'Inactivo',
                            ])
                            ->required()
                            ->inline()
                            ->inlineLabel(false)
                            ->default(true),

                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Estado')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Actualizado')
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
            'index' => Pages\ListMaintenanceItems::route('/'),
            'create' => Pages\CreateMaintenanceItem::route('/create'),
            'edit' => Pages\EditMaintenanceItem::route('/{record}/edit'),
        ];
    }
}
