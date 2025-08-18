<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Maintenance extends Model
{
    protected $fillable = [
        'vehicle_id',
        'maintenance_item_id',
        'mileage_at_service',
        'service_date',
        // valorizado del servicio
        'labor_cost',
        'parts_cost',
        'extra_cost',
        'total_cost',
        // Pastillas de freno delanteras
        'front_left_brake_pad',
        'front_right_brake_pad',
        // Pastillas de freno traseras
        'rear_left_brake_pad',
        'rear_right_brake_pad',
        // Fecha de último registro
        'progres_bar',
        'notes_valorization',
        'photo',
        'file',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function maintenanceItem()
    {
        return $this->belongsTo(
            related: MaintenanceItem::class,
            foreignKey: 'maintenance_item_id',
        );
    }
}
