<?php

use Illuminate\Support\Facades\Route;
use Modules\FlightMap\Http\Controllers\Api\MapController;

// All prefixed with /flightmap (see RouteServiceProvider)
Route::get('/my', [MapController::class, 'myFlights'])->name('my');
Route::get('/all', [MapController::class, 'allFlights'])->name('all');
Route::get('/aircraft', [MapController::class, 'aircraft'])->name('aircraft');
Route::get('/pilots', [MapController::class, 'pilots'])->name('pilots');
