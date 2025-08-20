<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Cargo;
use App\Models\Driver;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Tables\Actions\ActionGroup;
use pxlrbt\FilamentExcel\Columns\Column;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\DriverResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use App\Filament\Resources\DriverResource\RelationManagers;
use Hugomyb\FilamentMediaAction\Tables\Actions\MediaAction;
use Asmit\FilamentUpload\Forms\Components\AdvancedFileUpload;

class DriverResource extends Resource
{
    protected static ?string $model = Driver::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $modelLabel = 'Empleado';

    protected static ?string $navigationGroup = 'Gestión de Personal';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Datos del Chofer')
                    ->description('Datos generales del chofer y documnetos')
                    ->icon('heroicon-o-users')
                    ->collapsible()
                    ->schema([
                        Forms\Components\Grid::make()
                            ->columns(4)
                            ->schema([
                                Forms\Components\Card::make('Datos Personales')
                                    ->columnSpan(1)
                                    ->schema([
                                        Forms\Components\TextInput::make('name')
                                            ->label('Nombre')
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('last_paternal_name')
                                            ->label('Apellido Paterno')
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('last_maternal_name')
                                            ->label('Apellido Materno')
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('dni')
                                            ->label('DNI')
                                            ->required()
                                            ->numeric(),
                                        Forms\Components\Radio::make('status')
                                            ->label('Estado')
                                            ->options([
                                                '1' => 'Activo',
                                                '0' => 'Inactivo',
                                            ])
                                            ->required()
                                            ->inline()
                                            ->inlineLabel(false)
                                            ->default(true),
                                        Forms\Components\Select::make('cargo_id')
                                            ->label('Cargo')
                                            ->options(Cargo::all()->pluck('name', 'id'))
                                            ->searchable('name')
                                            ->required()
                                            ->native(false),
                                    ]),

                                Forms\Components\Grid::make('Documento')
                                    ->columnSpan(3)
                                    ->schema([
                                        Forms\Components\Grid::make()
                                            ->schema([
                                                // Forms\Components\FileUpload::make('file')
                                                AdvancedFileUpload::make('file')
                                                    ->label('Documento')
                                                    ->default(null)
                                                    ->columnSpanFull()
                                                    ->visibility('public')
                                                    ->directory('documents')
                                                    ->acceptedFileTypes(['application/pdf'])
                                                    ->pdfPreviewHeight(800),
                                            ]),

                                    ]),
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
            ->searchable()
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Nombre')
                    ->getStateUsing(fn($record) => $record->name . ' ' . $record->last_paternal_name . ' ' . $record->last_maternal_name)
                    ->searchable(['name', 'last_paternal_name', 'last_maternal_name'])
                    ->sortable(),
                Tables\Columns\TextColumn::make('dni')
                    ->label('DNI')

                    ->sortable(),
                Tables\Columns\TextColumn::make('cargo.name')
                    ->label('Cargo')
                    ->badge()
                    ->sortable(),
                // Tables\Columns\TextColumn::make('file')
                //     ->searchable(),
                Tables\Columns\IconColumn::make('status')
                    ->label('Estado')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('cargo_id')
                    ->label('Cargo')
                    ->options(Cargo::where('status', true)->pluck('name', 'id'))
                    ->searchable()
                    ->native(false),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        '1' => 'Activo',
                        '0' => 'Inactivo',
                    ])
                    ->native(false),

            ])
            ->actions([
                MediaAction::make('pdf')
                    ->label('')
                    ->media(fn($record) => $record->file ? asset('storage/' . $record->file) : null)
                    ->icon('bi-file-pdf-fill')
                    ->visible(fn($record) => ! empty($record->file))
                    ->color('danger'),
                ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    ExportBulkAction::make()
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
                                ->withFilename(date('Y-m-d') . ' - export'),
                        ]),
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
            'index' => Pages\ListDrivers::route('/'),
            'create' => Pages\CreateDriver::route('/create'),
            'edit' => Pages\EditDriver::route('/{record}/edit'),
        ];
    }
}
