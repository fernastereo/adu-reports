<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\OpportunityController;
use App\Http\Controllers\SalesPersonController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('/reports/appointmentreport/{startDate}/{endDate}', [AppointmentController::class, 'appointmentReport']);
Route::get('/reports/contactreport/{startDate}', [ContactController::class, 'contactReport']);
Route::post('/reports/exportappointments', [AppointmentController::class, 'exportData']);
Route::post('/reports/exportcontacts', [ContactController::class, 'exportData']);
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
