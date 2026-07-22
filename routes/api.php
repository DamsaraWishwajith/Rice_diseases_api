<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\DiseaseController;
use App\Http\Controllers\Api\DiseaseReportController;
use App\Http\Controllers\Api\FarmerController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::put('/update-profile/{id}', [AuthController::class, 'updateProfile']);

Route::get('/farmers', [FarmerController::class, 'index']);
Route::post('/farmers', [FarmerController::class, 'store']);

Route::post('/disease-info', [DiseaseController::class, 'getDiseaseInfo']);

Route::get('/disease-reports', [DiseaseReportController::class, 'index']);
Route::post('/disease-reports', [DiseaseReportController::class, 'store']);
Route::post('/get-supervisor-reports', [DiseaseReportController::class, 'getSupervisorReports']);
Route::post('/get-district-alerts', [DiseaseReportController::class, 'getDistrictAlerts']);
Route::post('/get-farmer-reports', [DiseaseReportController::class, 'getFarmerReports']);

Route::get('/test-fcm', function (Illuminate\Http\Request $request) {
    $district = $request->query('district', 'Anuradhapura');
    $disease = $request->query('disease', 'Blast');
    $username = $request->query('username', 'TestSupervisor');

    $firebaseService = new \App\Services\FirebaseService();
    $topic = 'district_' . $district;
    $title = "New Disease Reported in " . $district;
    $body = "Supervisor " . $username . " reported " . $disease;

    $success = $firebaseService->sendNotificationToTopic($topic, $title, $body, [
        'disease' => $disease,
        'supervisor' => $username,
    ]);

    return response()->json([
        'success' => $success,
        'message' => $success ? 'Notification sent successfully' : 'Notification dispatch failed'
    ]);
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


