<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaintenanceItem extends Model
{
    protected $fillable = [
        'name',
        'is_active',
        'interval_km',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
