<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\MutationController;
use App\Http\Controllers\TransactionController;
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

Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::post('top_up', [TransactionController::class, 'top_up']);
    Route::post('transfer', [TransactionController::class, 'transfer']);
    Route::post('withdraw', [TransactionController::class, 'withdraw']);
    Route::get('mutation/{user_id}', [MutationController::class, 'mutation']);

    Route::post('logout', [AuthController::class, 'logout']);
});
