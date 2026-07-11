<?php

use App\Http\Controllers\Pages\Settings\SettingsController;
use App\Http\Controllers\Pages\Ebmr\Logbook\ProductionEnvironmentController;
use Illuminate\Support\Facades\Route;

Route::get('/settings/general-policy', [SettingsController::class, 'index'])->name('pages.settings.general_policy');
Route::post('/settings/general-policy', [SettingsController::class, 'update'])->name('pages.settings.general_policy.update');

Route::get('/ebmr/logbooks/production-environment', [ProductionEnvironmentController::class, 'index'])->name('pages.ebmr.logbooks.production_environment');
Route::get('/ebmr/logbooks/production-environment/{distribution_id}', [ProductionEnvironmentController::class, 'show'])->name('pages.ebmr.logbooks.production_environment.show');
