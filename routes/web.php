<?php

use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\ChannelController as AdminChannelController;
use App\Http\Controllers\ChannelController;
use App\Http\Controllers\FixtureController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\TeamController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/jogo/{slug}-{fixture}', FixtureController::class)
    ->where(['slug' => '[a-z0-9-]+', 'fixture' => '[0-9]+'])
    ->name('fixtures.show');
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('/times', [TeamController::class, 'index'])->name('teams.index');
Route::get('/time/{team:slug}', [TeamController::class, 'show'])->name('teams.show');
Route::get('/canais', [ChannelController::class, 'index'])->name('channels.index');
Route::get('/canal/{channel:slug}', [ChannelController::class, 'show'])->name('channels.show');
Route::get('/jogos', [HomeController::class, 'redirectToDate'])->name('fixtures.redirect-to-date');
Route::get('/jogos/{date}', [HomeController::class, 'byDate'])
    ->where('date', '\\d{4}-\\d{2}-\\d{2}')
    ->name('fixtures.by-date');
Route::middleware('guest')->group(function () {
    Route::get('/admin/login', [AdminAuthController::class, 'create'])->name('admin.login');
    Route::post('/admin/login', [AdminAuthController::class, 'store'])->middleware('throttle:5,1')->name('admin.login.store');
});
Route::prefix('admin')->middleware('auth')->name('admin.')->group(function () {
    Route::get('/canais', [AdminChannelController::class, 'index'])->name('channels.index');
    Route::put('/canais/{channel}', [AdminChannelController::class, 'update'])->name('channels.update');
    Route::post('/sair', [AdminAuthController::class, 'destroy'])->name('logout');
});
