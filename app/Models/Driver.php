<?php

namespace App\Models;

use App\Models\DriverLicense;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Driver extends Model
{
    protected $fillable = [
        'name',
        'last_paternal_name',
        'last_maternal_name',
        'dni',
        'cargo_id',
        'file',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
        'cargo_id' => 'integer',
    ];

    public function getFullNameAttribute()
    {
        return "{$this->name} {$this->last_paternal_name} {$this->last_maternal_name}";
    }

    public function cargo()
    {
        return $this->belongsTo(Cargo::class);
    }

    public function driverLicenses(): HasMany
    {
        return $this->hasMany(
            related: DriverLicense::class,
            foreignKey: 'driver_id',
        );
    }

    // public function driverMineAssigments(): HasMany
    // {
    //     return $this->hasMany(
    //         related: DriverMineAssigment::class,
    //         foreignKey: 'driver_id',
    //     );
    // }
}
