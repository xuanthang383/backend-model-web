<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
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

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});
Route::post('/login', function () {
    return ['dungtq test post hihi' => app()->version()];
});           

Route::post('/register', [RegisteredUserController::class, 'store'])
    ->middleware('guest')
    ->name('api.register');
    
Route::post('/login', [AuthenticatedSessionController::class, 'store'])
    ->middleware('guest')
    ->name('api.login');

// Route::get('/access', [AuthenticatedSessionController::class, 'firstAccess']);
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
