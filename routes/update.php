<?php

use App\Http\Controllers\UpdateController;
use Illuminate\Support\Facades\Route;
/*
|--------------------------------------------------------------------------
| update Routes
|--------------------------------------------------------------------------
|
| This route is responsible for handling the update process
|
*/

Route::get('/', [UpdateController::class, 'init'])->name('update.init');
Route::get('/complete', [UpdateController::class, 'complete'])->name('update.complete');
