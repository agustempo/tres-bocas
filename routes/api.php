<?php

use App\Http\Controllers\Api\TideDataController;
use Illuminate\Support\Facades\Route;

Route::get('/tide-data', TideDataController::class);
