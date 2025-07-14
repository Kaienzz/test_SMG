<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GameController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/game', [GameController::class, 'index']);
Route::get('/game/state', [GameController::class, 'state']);
Route::post('/game/move', [GameController::class, 'move']);
Route::post('/game/next', [GameController::class, 'next']);

// Additional routes can be defined here.