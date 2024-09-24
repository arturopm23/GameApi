<?php

use App\Http\Controllers\GameController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:api');

Route::post('/players', [UserController::class, 'store']);
Route::post('/players/login', [UserController::class, 'login']);
Route::put('/players/{id}', [UserController::class, 'updateName'])->middleware('auth:api');
Route::post('/players/{id}/games', [GameController::class, 'rollDice'])->middleware('auth:api');
Route::delete('/players/{id}/games', [GameController::class, 'deleteGames'])->middleware('auth:api');


