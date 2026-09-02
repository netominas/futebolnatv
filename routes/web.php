<?php

use App\Http\Controllers\FixtureController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/jogo/{slug}-{fixture}', FixtureController::class)
    ->where(['slug' => '[a-z0-9-]+', 'fixture' => '[0-9]+'])
    ->name('fixtures.show');
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('/jogos', [HomeController::class, 'redirectToDate'])->name('fixtures.redirect-to-date');
Route::get('/jogos/{date}', [HomeController::class, 'byDate'])
    ->where('date', '\\d{4}-\\d{2}-\\d{2}')
    ->name('fixtures.by-date');
