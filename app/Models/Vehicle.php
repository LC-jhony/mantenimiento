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
}
