<?php
use App\Http\Controllers\Pages\MaterData\DepartmentController;
use App\Http\Controllers\Pages\MaterData\UnitController;
use App\Http\Controllers\Pages\MaterData\MarketController;
use App\Http\Controllers\Pages\MaterData\ProductNameController;
use App\Http\Controllers\Pages\MaterData\SpecificationController;
use App\Http\Controllers\UploadDataController;
use App\Http\Middleware\CheckLogin;
use Illuminate\Support\Facades\Route;

Route::get('/upload', [UploadDataController::class, 'index'])->name('upload.form_load');
Route::POST('/import', [UploadDataController::class, 'import'])->name('upload.import');
Route::POST('/import_permission', [UploadDataController::class, 'import_permission'])->name('upload.import_permission');

// 1. Dữ Liệu Gốc (Master Data)
Route::prefix('/materData')
    ->name('pages.materData.')
    ->middleware(CheckLogin::class)
    ->group(function () {
        Route::prefix('/department')->name('department.')->controller(DepartmentController::class)->group(function () {
            Route::get('', 'index')->name('list');
            Route::post('store', 'store')->name('store');
            Route::post('update', 'update')->name('update');
            Route::post('deActive', 'deActive')->name('deActive');
        });
        Route::prefix('/unit')->name('unit.')->controller(UnitController::class)->group(function () {
            Route::get('', 'index')->name('list');
            Route::post('store', 'store')->name('store');
            Route::post('update', 'update')->name('update');
            Route::post('deActive', 'deActive')->name('deActive');
        });
        Route::prefix('/productName')->name('productName.')->controller(ProductNameController::class)->group(function () {
            Route::get('', 'index')->name('list');
            Route::post('store', 'store')->name('store');
            Route::post('update', 'update')->name('update');
            Route::post('deActive', 'deActive')->name('deActive');
        });
        Route::prefix('/specification')->name('specification.')->controller(SpecificationController::class)->group(function () {
            Route::get('', 'index')->name('list');
            Route::post('store', 'store')->name('store');
            Route::post('update', 'update')->name('update');
            Route::post('destroy', 'destroy')->name('destroy');
        });
        Route::prefix('/market')->name('market.')->controller(MarketController::class)->group(function () {
            Route::get('', 'index')->name('list');
            Route::post('store', 'store')->name('store');
            Route::post('update', 'update')->name('update');
            Route::post('delete', 'delete')->name('delete');
        });
        Route::prefix('/materialRole')->name('materialRole.')->controller(\App\Http\Controllers\Pages\MaterData\MaterialRoleController::class)->group(function () {
            Route::get('', 'index')->name('list');
            Route::post('store', 'store')->name('store');
            Route::post('update', 'update')->name('update');
            Route::post('delete', 'delete')->name('delete');
        });
        Route::prefix('/materialSpec')->name('materialSpec.')->controller(\App\Http\Controllers\Pages\MaterData\MaterialSpecController::class)->group(function () {
            Route::get('', 'index')->name('list');
            Route::post('store', 'store')->name('store');
            Route::post('update', 'update')->name('update');
            Route::post('delete', 'delete')->name('delete');
        });
        Route::prefix('/designation')->name('designation.')->controller(\App\Http\Controllers\Pages\MaterData\DesignationController::class)->group(function () {
            Route::get('', 'index')->name('list');
            Route::post('store', 'store')->name('store');
            Route::post('update', 'update')->name('update');
            Route::post('deActive', 'deActive')->name('deActive');
        });
        Route::prefix('/seal')->name('seal.')->controller(\App\Http\Controllers\Pages\MaterData\SealController::class)->group(function () {
            Route::get('', 'index')->name('list');
            Route::post('store', 'store')->name('store');
            Route::post('update', 'update')->name('update');
            Route::post('deActive', 'deActive')->name('deActive');
        });

    });
