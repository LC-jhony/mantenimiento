<?php

namespace Database\Seeders;

use App\Models\MaintenanceType;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class MaintenanceTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $maintenanceTypes = [
            [
                'name' => 'FILTRO DE ACEITE DE MOTOR',
                'service_type' => 'L/M',
                'interval_km' => 7500,
                'labor_cost' => 25.00,
                'parts_cost' => 15.00
            ],
            [
                'name' => 'FILTRO DE COMBUSTIBLE',
                'service_type' => 'L/M',
                'interval_km' => 15000,
                'labor_cost' => 30.00,
                'parts_cost' => 20.00
            ],
            [
                'name' => 'PATILLAS DE FRENO', // Agregado como solicitaste
                'service_type' => 'L',
                'interval_km' => 30000,
                'labor_cost' => 45.00,
                'parts_cost' => 60.00
            ]
        ];

        foreach ($maintenanceTypes as $type) {
            MaintenanceType::create($type);
        }
    }
}
