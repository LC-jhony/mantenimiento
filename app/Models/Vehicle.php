<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    protected $fillable = [
        'code',
        'placa',
        'marca',
        'unidad',
        'property_card',
        'status',
        'current_mileage',
    ];

    protected $casts = [
        'year' => 'integer',
        'current_mileage' => 'integer',
    ];
    protected $appends = [
        'oil_progress',
        'brake_progress',
    ];
    public function maintenances()
    {
        return $this->hasMany(Maintenance::class);
    }
    public function lastMaintenance()
    {
        return $this->hasOne(Maintenance::class)->latestOfMany();
    }

    // 🔹 Aceite dinámico
    public function getOilProgressAttribute()
    {
        return $this->lastMaintenance?->progres_bar ?? null;
    }

    // 🔹 Pastillas dinámicas (promedio)
    public function getBrakeProgressAttribute()
    {
        $last = $this->lastMaintenance;
        if (!$last) return null;

        $pads = [
            $last->front_left_brake_pad,
            $last->front_right_brake_pad,
            $last->rear_left_brake_pad,
            $last->rear_right_brake_pad,
        ];

        $pads = array_filter($pads, fn($val) => is_numeric($val));
        return count($pads) ? array_sum($pads) / count($pads) : null;
    }
    public function lastMaintenanceMileage()
    {
        return $this->hasOne(Maintenance::class)->latestOfMany('service_date')
            ->select('maintenances.mileage_at_service', 'maintenances.vehicle_id');
    }
}
