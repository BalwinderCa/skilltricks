<?php

use App\Http\Controllers\Backend\Payments\Yookassa\YookassaPaymentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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


Route::post('/youkassa/process', [YookassaPaymentController::class, 'process']);

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
