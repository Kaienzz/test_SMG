<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GameController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/game', [GameController::class, 'index']);
Route::post('/game/roll', [GameController::class, 'roll']);
Route::post('/game/move', [GameController::class, 'move']);
Route::post('/game/next', [GameController::class, 'next']);
