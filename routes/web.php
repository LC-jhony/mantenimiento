<?php

use App\Http\Controllers\PrintMaintenanceVehicle;
use Illuminate\Support\Facades\Route;

Route::get('/print-maintenance-vehicle', PrintMaintenanceVehicle::class)
    ->name('print-maintenance-vehicle');
// Route::get('/', function () {
//     return view('welcome');
// });
