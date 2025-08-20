<?php

namespace App\Models;

use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;

class Maintenance extends Model
{
    protected $fillable = [
        'vehicle_id',
        'maintenance_item_id',
        'mileage_at_service',
        'interval_km',
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
        'progres_bar',
        'notes_valorization',
        'photo',
        'file',
    ];

    protected $casts = [
        'mileage_at_service'   => 'integer',
        'interval_km'          => 'integer',
        'service_date'         => 'date',
        'labor_cost'           => 'decimal:2',
        'parts_cost'           => 'decimal:2',
        'extra_cost'           => 'decimal:2',
        'total_cost'           => 'decimal:2',
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



    public function isDue($currentMileage)
    {
        return $currentMileage >= $this->next_service_mileage;
    }

    public function kmRemaining($currentMileage)
    {
        return $this->next_service_mileage - $currentMileage;
    }
    // App\Models\Maintenance.php

}
