<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\LogRequests;
use App\Http\Middleware\CheckNegativeBalance;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');


Route::post('/register', [RegisterController::class, 'register'])->middleware(LogRequests::class);
Route::post('/login', [LoginController::class, 'login'])->middleware(LogRequests::class);
Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('wallet')->group(function () {
        Route::post('/deposit', [WalletController::class, 'deposit']);
        Route::post('/transfer', [WalletController::class, 'transfer']);
        Route::post('/reverse/{transaction}', [WalletController::class, 'reverse']);
        Route::get('/balance', [WalletController::class, 'balance']);
        Route::get('/transactions', [WalletController::class, 'transactions']);
    })->middleware([CheckNegativeBalance::class, LogRequests::class]);
});
