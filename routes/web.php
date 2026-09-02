<?php

use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/jogos', [HomeController::class, 'redirectToDate'])->name('fixtures.redirect-to-date');
Route::get('/jogos/{date}', [HomeController::class, 'byDate'])
    ->where('date', '\\d{4}-\\d{2}-\\d{2}')
    ->name('fixtures.by-date');
