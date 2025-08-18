<?php

namespace Database\Seeders;

use App\Models\MaintenanceItem;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class MaintenanceItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $items = [
            ['name' => 'FILTRO DE ACEITE DE MOTOR'],
            ['name' => 'FILTRO DE COMBUSTIBLE'],
            ['name' => 'FILTRO DE AIRE'],
            ['name' => 'FILTRO P/POLVO A/C'],
            ['name' => 'FILTRO TAMIZ'],
            ['name' => 'ANILLO TAPON DE CARTER'],
            ['name' => 'ACEITE SINTETICO - MOTOR'],
            ['name' => 'ACEITE DE CAJA DE CAMBIOS'],
            ['name' => 'ACEITE DIFERENCIAL'],
            ['name' => 'ACEITE DE DIRECCION ATF'],
            ['name' => 'LIQUIDO REFRIG. PARA MOTOR'],
            ['name' => 'LIQUIDO PARA FRENOS/EMBRIAGUE'],
            ['name' => 'CONCENTRADO LAVACRISTALES'],
        ];

        foreach ($items as $item) {
            MaintenanceItem::create($item);
        }
    }
}
