<?php

namespace App\Models;

use App\Models\Driver;
use Illuminate\Database\Eloquent\Model;

class Cargo extends Model
{
    protected $fillable = [
        'name',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function drivers()
    {
        return $this->hasMany(Driver::class);
    }
}
