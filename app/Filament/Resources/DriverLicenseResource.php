<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Driver;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\DriverLicense;
use Filament\Resources\Resource;
use Filament\Tables\Actions\ActionGroup;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\DriverLicenseResource\Pages;
use Hugomyb\FilamentMediaAction\Tables\Actions\MediaAction;
use Asmit\FilamentUpload\Forms\Components\AdvancedFileUpload;
use App\Filament\Resources\DriverLicenseResource\RelationManagers;
use Dompdf\Css\Color;

class DriverLicenseResource extends Resource
{
    protected static ?string $model = DriverLicense::class;

    protected static ?string $navigationIcon = 'heroicon-o-identification';

    protected static ?string $modelLabel = 'Licencia';

    protected static ?string $navigationGroup = 'Gestión de Personal';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Datos del Conductor')
                    ->description('Datos generales del chofer y documnetos')
                    ->schema([
                        Forms\Components\Grid::make()
                            ->columns(4)
                            ->schema([
                                Forms\Components\Card::make('Datos Personales')
                                    ->description('Datos generales del chofer y documnetos')
                                    ->columnSpan(1)
                                    ->schema([
                                        Forms\Components\Select::make('driver_id')
                                            ->label('Chofer')
                                            ->searchable()
                                            ->options(Driver::all()->pluck('name', 'id'))
                                            ->getSearchResultsUsing(fn(string $search): array => Driver::where('dni', 'like', "%{$search}%")->limit(50)->get()->mapWithKeys(function ($driver) {
                                                return [$driver->id => "{$driver->name} {$driver->last_paternal_name} {$driver->last_maternal_name}"];
                                            })->toArray())
                                            ->getOptionLabelsUsing(fn(array $values): array => Driver::whereIn('id', $values)->get()->mapWithKeys(function ($driver) {
                                                return [$driver->id => "{$driver->name} {$driver->last_paternal_name} {$driver->last_maternal_name}"];
                                            })->toArray())
                                            ->required(),
                                        Forms\Components\TextInput::make('license_number')
                                            ->label('Número de Licencia')
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\DatePicker::make('expiration_date')
                                            ->label('Fecha de Vencimiento')
                                            ->required()
                                            ->native(false),
                                        Forms\Components\TextInput::make('license_type')
                                            ->required()
                                            ->maxLength(255),
                                    ]),
                                Forms\Components\Card::make('Documentos del Chofer')
                                    ->columnSpan(3)
                                    ->schema([
                                        AdvancedFileUpload::make('file')
                                            ->label('Documento')
                                            // ->multiple()
                                            ->columnSpanFull()
                                            ->visibility('public')
                                            ->directory('Licencias')
                                            ->default(null)
                                            ->acceptedFileTypes(['application/pdf'])
                                            ->pdfPreviewHeight(800),
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
                Tables\Columns\TextColumn::make('driver.full_name')
                    ->label('Chofer')
                    ->getStateUsing(fn($record) => $record->driver->name . ' ' . $record->driver->last_paternal_name . ' ' . $record->driver->last_maternal_name)
                    ->searchable(['drivers.name', 'drivers.last_paternal_name', 'drivers.last_maternal_name'])
                    ->sortable(),
                Tables\Columns\TextColumn::make('license_number')
                    ->label('Número Licencia')
                    ->searchable(),
                Tables\Columns\TextColumn::make('expiration_date')
                    ->label('Fecha Vencimiento')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('license_type')
                    ->label('Tipo Licencia')
                    ->badge()
                    ->searchable(),
                Tables\Columns\TextColumn::make('status_label')
                    ->label('Estado')
                    ->getStateUsing(function ($record) {
                        $expirationDate = \Carbon\Carbon::parse($record->expiration_date);
                        $today = now();

                        // Si ya venció
                        if ($expirationDate->isPast()) {
                            return 'Vencido';
                        }

                        $daysRemaining = $today->diffInDays($expirationDate);

                        return match (true) {
                            $daysRemaining <= 3 => 'Crítico',      // 3 días o menos
                            $daysRemaining <= 30 => 'Por vencer',  // Entre 4 y 30 días
                            default => 'Vigente'                   // Más de 30 días
                        };
                    })
                    ->badge()
                    ->color(function ($state) {
                        return match ($state) {
                            'Vigente' => 'success',
                            'Por vencer' => 'warning',
                            'Crítico' => 'gray',
                            'Vencido' => 'danger',
                            default => 'success',
                        };
                    })
                    ->icon(function ($state) {
                        return match ($state) {
                            'Vigente' => 'heroicon-o-shield-check',
                            'Por vencer' => 'heroicon-o-exclamation-triangle',
                            'Crítico' => 'heroicon-o-fire',
                            'Vencido' => 'heroicon-o-x-circle',
                            default => 'heroicon-o-shield-check',
                        };
                    })
                    ->sortable()
                    ->searchable(),

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
                Tables\Columns\TextColumn::make('deleted_at')
                    ->label('Eliminado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([

                /**
                 * Filtro para el estado de las licencias de conducir
                 *
                 * Este filtro permite filtrar las licencias por su estado de vencimiento:
                 * - Vigente: Licencias con más de 30 días para vencer
                 * - Por vencer: Licencias que vencen entre 4 y 30 días
                 * - Crítico: Licencias que vencen en 3 días o menos
                 * - Vencido: Licencias que ya han vencido
                 */
                Tables\Filters\SelectFilter::make('status_label')
                    // Etiqueta que se muestra en la interfaz del filtro
                    ->label('Estado de Licencia')

                    // Opciones disponibles en el dropdown del filtro
                    // Formato: 'valor_interno' => 'Texto mostrado al usuario'
                    ->options([
                        'vigente' => 'Vigente',        // Licencias con más de 30 días
                        'por_vencer' => 'Por vencer',  // Licencias entre 4-30 días
                        'critico' => 'Crítico',        // Licencias con 3 días o menos
                        'vencido' => 'Vencido',        // Licencias ya vencidas
                    ])
                    ->native(false)

                    /**
                     * Query personalizada que se ejecuta cuando se selecciona una opción
                     *
                     * @param  Builder  $query  - Constructor de consulta Eloquent
                     * @param  array  $data  - Datos del filtro ['value' => 'opcion_seleccionada']
                     * @return Builder - Query modificada según el filtro seleccionado
                     */
                    ->query(function (Builder $query, array $data): Builder {
                        // Si no hay valor seleccionado, retorna la query sin modificar
                        if (! $data['value']) {
                            return $query;
                        }
                        // Obtiene la fecha actual para comparaciones
                        $today = now();

                        // Aplica el filtro según la opción seleccionada
                        return match ($data['value']) {
                            // VIGENTE: Fecha de vencimiento > hoy + 30 días
                            'vigente' => $query->where('expiration_date', '>', $today->copy()->addDays(30)),

                            // POR VENCER: Fecha entre hoy + 3 días y hoy + 30 días
                            'por_vencer' => $query->where('expiration_date', '>', $today->copy()->addDays(3))
                                ->where('expiration_date', '<=', $today->copy()->addDays(30)),

                            // CRÍTICO: Fecha entre hoy y hoy + 3 días
                            'critico' => $query->where('expiration_date', '>', $today)
                                ->where('expiration_date', '<=', $today->copy()->addDays(3)),

                            // VENCIDO: Fecha de vencimiento < hoy
                            'vencido' => $query->where('expiration_date', '<', $today),

                            // Caso por defecto: retorna query sin modificar
                            default => $query,
                        };
                    })
                    // Texto que se muestra cuando no hay ninguna opción seleccionada
                    ->placeholder('Todos los estados')
                    // Permite seleccionar solo una opción a la vez (no múltiple)
                    ->multiple(false),
                Tables\Filters\SelectFilter::make('driver_id')
                    ->options(Driver::where('status', true)->pluck('name', 'id'))
                    ->multiple()
                    ->label('Conductor')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                MediaAction::make('pdf')
                    ->label('')
                    ->media(fn($record) => $record->file ? asset('storage/' . $record->file) : null)
                    // ->iconButton()
                    ->icon('bi-file-pdf-fill')
                    ->color('danger')
                    ->visible(fn($record) => ! empty($record->file)),
                ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),

                ]),
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
            'index' => Pages\ListDriverLicenses::route('/'),
            'create' => Pages\CreateDriverLicense::route('/create'),
            'edit' => Pages\EditDriverLicense::route('/{record}/edit'),
        ];
    }
}
