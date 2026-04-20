<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\DiseaseController;
use App\Http\Controllers\Api\DiseaseReportController;
use App\Http\Controllers\Api\FarmerController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/farmers', [FarmerController::class, 'index']);
Route::post('/farmers', [FarmerController::class, 'store']);

Route::post('/disease-info', [DiseaseController::class, 'getDiseaseInfo']);

Route::get('/disease-reports', [DiseaseReportController::class, 'index']);
Route::post('/disease-reports', [DiseaseReportController::class, 'store']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
