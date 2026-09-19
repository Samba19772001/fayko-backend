<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\VerificationController;

Route::get('/admin/login', [AdminAuthController::class, 'showLoginForm'])->name('admin.login');
Route::post('/admin/login', [AdminAuthController::class, 'login'])->name('admin.login.submit');
Route::post('/admin/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');

Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [VerificationController::class, 'index'])->name('dashboard');
    Route::post('/verifications/{user}/valider', [VerificationController::class, 'valider'])->name('verifications.valider');
    Route::post('/verifications/{user}/rejeter', [VerificationController::class, 'rejeter'])->name('verifications.rejeter');
    
    Route::get('/statistiques', [\App\Http\Controllers\Admin\StatistiqueController::class, 'index'])->name('statistiques');
});