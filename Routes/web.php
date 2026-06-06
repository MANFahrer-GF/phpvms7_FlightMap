<?php

use Illuminate\Support\Facades\Route;
use Modules\FlightMap\Http\Controllers\Frontend\IndexController;

Route::get('/flightmap', [IndexController::class, 'index'])->name('flightmap.index');
