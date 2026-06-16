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


// Yookassa server-to-server webhook. Cannot use auth (called by Yookassa), so:
//  - throttle to blunt abuse/replay
//  - the handler MUST verify the notification (Yookassa IP allowlist and/or
//    re-fetch the payment by id) before granting any subscription. See controller.
Route::post('/youkassa/process', [YookassaPaymentController::class, 'process'])
    ->middleware('throttle:30,1');

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
