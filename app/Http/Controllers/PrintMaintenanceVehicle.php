<?php

namespace App\Http\Controllers;

use App\Models\Maintenance;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class PrintMaintenanceVehicle extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $month = $request->integer('month');
        $startDate = $request->date('start_date');
        $endDate = $request->date('end_date');
        $dateColumn = 'service_date';
        // configurar fecha para la consulta
        $query = Maintenance::with(['vehicle', 'maintenanceItem']);
        if ($month) {
            $query->whereMonth($dateColumn, $month);
        }
        if ($startDate) {
            $query->whereDate($dateColumn, '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate($dateColumn, '<=', $endDate);
        }
        //
        $vehicles = $query->get()->groupBy('vehicle_id');
        $pdf = Pdf::loadView('pdf.print_maintenance_vehicle', [
            'vehicles' => $vehicles,
            'filters' => [
                'month' => $month,
                'startDate' => $startDate,
                'endDate' => $endDate,
            ],
        ])->setPaper('A4', 'portrait');

        return $pdf->stream();
    }
}
