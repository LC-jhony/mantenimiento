<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use App\Models\Cargo;
use App\Models\Driver;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class DriverImport implements ToCollection, WithBatchInserts, WithChunkReading, WithHeadingRow
{
    protected $additionalData = [];

    protected $customImportData = [];

    protected $importStats = [
        'total' => 0,
        'imported' => 0,
        'updated' => 0,
        'skipped' => 0,
        'errors' => 0,
    ];

    protected $validationErrors = [];

    protected $cargoCache = [];

    public function collection(Collection $rows)
    {
        $this->importStats['total'] = $rows->count();

        $successCount = 0;
        $updatedCount = 0;
        $errorCount = 0;
        $duplicatesInExcel = 0;
        $duplicatesNotImported = 0;
        $skippedCount = 0;
        $errors = [];
        $processedDnis = [];
        $duplicateDnis = [];

        // Pre-load existing drivers to optimize database queries
        $existingDrivers = Driver::pluck('id', 'dni')->toArray();

        DB::beginTransaction();

        try {
            foreach ($rows as $rowIndex => $row) {
                $rowNumber = $rowIndex + 2; // +2 because of 0-index and header row

                try {
                    // Use helper methods for flexible column mapping
                    $dni = $this->getDniFromRow($row);
                    $name = $this->getName($row);

                    // Skip empty rows with better validation
                    if (empty($dni) || empty($name)) {
                        $skippedCount++;
                        Log::info('Skipped empty row', ['row' => $rowNumber, 'data' => $row]);

                        continue;
                    }

                    // Validate row data
                    $validatedData = $this->validateRowData($row, $rowNumber);
                    if (! $validatedData) {
                        $errorCount++;

                        continue;
                    }

                    // Check for duplicates in Excel
                    if (in_array($dni, $processedDnis)) {
                        $duplicatesInExcel++;
                        $duplicateDnis[] = $dni;
                        $duplicatesNotImported++;

                        Log::warning('Duplicate DNI found in Excel', [
                            'dni' => $dni,
                            'row' => $rowNumber,
                            'data' => $row,
                        ]);

                        continue;
                    }

                    $processedDnis[] = $dni;

                    // Get or create cargo with caching
                    $cargo = $this->getOrCreateCargoWithCache($row);

                    // Prepare driver data
                    $driverData = [
                        'name' => $validatedData['name'],
                        'last_paternal_name' => $validatedData['last_paternal_name'],
                        'last_maternal_name' => $validatedData['last_maternal_name'],
                        'cargo_id' => $cargo?->id,
                        'status' => true, // Default to active
                    ];

                    // Check if driver exists
                    $isUpdate = isset($existingDrivers[$dni]);

                    // Create or update driver
                    Driver::updateOrCreate(
                        ['dni' => $dni],
                        $driverData
                    );

                    if ($isUpdate) {
                        $updatedCount++;
                        Log::info('Driver updated', ['dni' => $dni, 'row' => $rowNumber]);
                    } else {
                        $successCount++;
                        Log::info('Driver created', ['dni' => $dni, 'row' => $rowNumber]);
                    }
                } catch (ValidationException $e) {
                    $errorCount++;
                    $errorMessage = 'Fila ' . $rowNumber . " con DNI {$dni}: " . implode(', ', $e->errors()['general'] ?? $e->errors());
                    $errors[] = $errorMessage;
                    $this->validationErrors[] = $errorMessage;

                    Log::error('Validation error importing driver', [
                        'row' => $rowNumber,
                        'data' => $row,
                        'errors' => $e->errors(),
                    ]);
                } catch (\Exception $e) {
                    $errorCount++;
                    $errorMessage = 'Fila ' . $rowNumber . " con DNI {$dni}: " . $e->getMessage();
                    $errors[] = $errorMessage;

                    Log::error('Error importing driver', [
                        'row' => $rowNumber,
                        'data' => $row,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            }

            // Update import stats
            $this->importStats['imported'] = $successCount;
            $this->importStats['updated'] = $updatedCount;
            $this->importStats['skipped'] = $skippedCount;
            $this->importStats['errors'] = $errorCount;

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Critical error during driver import', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }

        // Send simplified notifications
        $this->sendImportNotifications($successCount, $updatedCount, $errorCount, $duplicatesInExcel, $duplicateDnis);
    }

    /**
     * Sistema de notificaciones simplificado - Solo 4 tipos
     */
    private function sendImportNotifications(int $successCount, int $updatedCount, int $errorCount, int $duplicatesInExcel, array $duplicateDnis)
    {
        // 1. Notificación de Éxito (nuevos registros)
        if ($successCount > 0) {
            $this->sendSuccessNotification($successCount);
        }

        // 2. Notificación de Datos Ya Registrados (actualizados)
        if ($updatedCount > 0) {
            $this->sendUpdatedNotification($updatedCount);
        }

        // 3. Notificación de Duplicados
        if ($duplicatesInExcel > 0) {
            $this->sendDuplicatesNotification($duplicatesInExcel, $duplicateDnis);
        }

        // 4. Notificación de Errores
        if ($errorCount > 0) {
            $this->sendErrorNotification($errorCount);
        }
    }

    /**
     * 1. Notificación de Éxito - Nuevos conductores importados
     */
    private function sendSuccessNotification(int $successCount)
    {
        Notification::make()
            ->title('✅ Importación Exitosa')
            ->body("{$successCount} conductores nuevos fueron importados correctamente.")
            ->success()
            ->duration(5000)
            ->send();
    }

    /**
     * 2. Notificación de Datos Ya Registrados - Conductores actualizados
     */
    private function sendUpdatedNotification(int $updatedCount)
    {
        Notification::make()
            ->title('🔄 Datos Actualizados')
            ->body("{$updatedCount} conductores ya existían y fueron actualizados con la nueva información.")
            ->info()
            ->duration(5000)
            ->send();
    }

    /**
     * 3. Notificación de Duplicados - DNIs duplicados en el Excel
     */
    private function sendDuplicatesNotification(int $duplicatesCount, array $duplicateDnis)
    {
        $uniqueDnis = array_unique($duplicateDnis);
        $exampleDnis = array_slice($uniqueDnis, 0, 3);

        $body = "Se encontraron {$duplicatesCount} registros duplicados en el archivo Excel y fueron omitidos.";

        if (count($exampleDnis) > 0) {
            $body .= "\n\nEjemplos de DNIs duplicados: " . implode(', ', $exampleDnis);
            if (count($uniqueDnis) > 3) {
                $remaining = count($uniqueDnis) - 3;
                $body .= " y {$remaining} más.";
            }
        }

        Notification::make()
            ->title('⚠️ Duplicados Detectados')
            ->body($body)
            ->warning()
            ->duration(8000)
            ->send();
    }

    /**
     * 4. Notificación de Errores - Registros con errores de validación
     */
    private function sendErrorNotification(int $errorCount)
    {
        $body = "Se encontraron {$errorCount} registros con errores de validación.";

        if (! empty($this->validationErrors)) {
            $examples = array_slice($this->validationErrors, 0, 2);
            $body .= "\n\nEjemplos de errores:\n• " . implode("\n• ", $examples);

            if (count($this->validationErrors) > 2) {
                $remaining = count($this->validationErrors) - 2;
                $body .= "\n... y {$remaining} errores más.";
            }
        }

        Notification::make()
            ->title('❌ Errores de Validación')
            ->body($body)
            ->danger()
            ->duration(10000)
            ->send();
    }

    // ========== MÉTODOS HELPER ==========

    /**
     * Validate row data with comprehensive validation rules
     */
    private function validateRowData($row, int $rowNumber): ?array
    {
        try {
            $dni = $this->getDniFromRow($row);
            $name = $this->getName($row);
            $lastPaternalName = $this->getLastPaternalName($row);
            $lastMaternalName = $this->getLastMaternalName($row);

            // Validation rules
            $rules = [
                'dni' => ['required', 'string', 'min:8', 'max:12', 'regex:/^[0-9]+$/'],
                'name' => ['required', 'string', 'min:2', 'max:255'],
                'last_paternal_name' => ['nullable', 'string', 'max:255'],
                'last_maternal_name' => ['nullable', 'string', 'max:255'],
            ];

            $data = [
                'dni' => $dni,
                'name' => $name,
                'last_paternal_name' => $lastPaternalName,
                'last_maternal_name' => $lastMaternalName,
            ];

            $validator = Validator::make($data, $rules, [
                'dni.required' => 'El DNI es obligatorio',
                'dni.regex' => 'El DNI debe contener solo números',
                'dni.min' => 'El DNI debe tener al menos 8 dígitos',
                'dni.max' => 'El DNI no puede tener más de 12 dígitos',
                'name.required' => 'El nombre es obligatorio',
                'name.min' => 'El nombre debe tener al menos 2 caracteres',
                'name.max' => 'El nombre no puede tener más de 255 caracteres',
            ]);

            if ($validator->fails()) {
                $errorMessage = 'Fila ' . $rowNumber . ': ' . implode(', ', $validator->errors()->all());
                $this->validationErrors[] = $errorMessage;

                Log::warning('Row validation failed', [
                    'row' => $rowNumber,
                    'data' => $data,
                    'errors' => $validator->errors()->all(),
                ]);

                return null;
            }

            return $data;
        } catch (\Exception $e) {
            Log::error('Error validating row data', [
                'row' => $rowNumber,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Get or create cargo with caching for better performance
     */
    private function getOrCreateCargoWithCache($row): ?Cargo
    {
        $cargoFields = ['CARGO', 'cargo', 'position', 'puesto', 'job_title'];
        $cargoName = null;

        foreach ($cargoFields as $field) {
            if (! empty($row[$field])) {
                $cargoName = trim((string) $row[$field]);
                break;
            }
        }

        if (empty($cargoName)) {
            return null;
        }

        // Use cache to avoid repeated database queries
        if (! isset($this->cargoCache[$cargoName])) {
            $this->cargoCache[$cargoName] = Cargo::firstOrCreate(
                ['name' => $cargoName],
                ['name' => $cargoName, 'status' => true]
            );
        }

        return $this->cargoCache[$cargoName];
    }

    /**
     * Obtener DNI de la fila con múltiples variaciones y validación mejorada
     */
    private function getDniFromRow($row): string
    {
        $dniFields = ['dni', 'documento', 'cedula', 'ci', 'numero_documento', 'document_number'];

        foreach ($dniFields as $field) {
            if (! empty($row[$field])) {
                $dni = trim((string) $row[$field]);
                // Remove any non-numeric characters for consistency
                $dni = preg_replace('/[^0-9]/', '', $dni);

                if ($this->isValidDni($dni)) {
                    return $dni;
                }
            }
        }

        return '';
    }

    /**
     * Obtener apellido paterno de la fila con sanitización
     */
    private function getLastPaternalName($row): ?string
    {
        $fields = [
            'apellido_paterno',
            'apellido paterno',
            'last_paternal_name',
            'primer_apellido',
            'primer apellido',
            'paternal_surname',
        ];

        foreach ($fields as $field) {
            if (! empty($row[$field])) {
                return $this->sanitizeText((string) $row[$field]);
            }
        }

        return null;
    }

    /**
     * Obtener apellido materno de la fila con sanitización
     */
    private function getLastMaternalName($row): ?string
    {
        $fields = [
            'apellido_materno',
            'apellido materno',
            'last_maternal_name',
            'segundo_apellido',
            'segundo apellido',
            'maternal_surname',
        ];

        foreach ($fields as $field) {
            if (! empty($row[$field])) {
                return $this->sanitizeText((string) $row[$field]);
            }
        }

        return null;
    }

    /**
     * Obtener nombre de la fila con sanitización
     */
    private function getName($row): string
    {
        $fields = [
            'name',
            'nombres',
            'nombre',
            'first_name',
            'nombre_completo',
            'full_name',
            'given_name',
        ];

        foreach ($fields as $field) {
            if (! empty($row[$field])) {
                $name = $this->sanitizeText((string) $row[$field]);

                return $name ?? '';
            }
        }

        return '';
    }

    /**
     * Sanitize and normalize text input
     */
    private function sanitizeText(?string $text): ?string
    {
        if (empty($text)) {
            return null;
        }

        // Remove extra whitespace and normalize
        $text = trim($text);
        $text = preg_replace('/\s+/', ' ', $text);

        // Convert to proper case for names
        return mb_convert_case($text, MB_CASE_TITLE, 'UTF-8');
    }

    /**
     * Validate DNI format more strictly
     */
    private function isValidDni(string $dni): bool
    {
        // Remove any non-numeric characters
        $dni = preg_replace('/[^0-9]/', '', $dni);

        // Check length (typically 8-12 digits for most countries)
        if (strlen($dni) < 8 || strlen($dni) > 12) {
            return false;
        }

        return true;
    }

    // ========== CONFIGURACIÓN ==========

    public function headingRow(): int
    {
        return 1; // First row contains headers
    }

    /**
     * Configure batch size for better performance
     */
    public function batchSize(): int
    {
        return 500; // Process 500 rows at a time
    }

    /**
     * Configure chunk size for memory efficiency
     */
    public function chunkSize(): int
    {
        return 1000; // Read 1000 rows at a time
    }

    /**
     * Get validation errors that occurred during import
     */
    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }

    /**
     * Clear validation errors
     */
    public function clearValidationErrors(): void
    {
        $this->validationErrors = [];
    }

    /**
     * Check if import has validation errors
     */
    public function hasValidationErrors(): bool
    {
        return ! empty($this->validationErrors);
    }

    /**
     * Obtener estadísticas de importación
     */
    public function getImportStats(): array
    {
        return $this->importStats;
    }

    public function setAdditionalData($data)
    {
        $this->additionalData = $data;
    }

    public function setCustomImportData($data)
    {
        $this->customImportData = $data;
    }
}
