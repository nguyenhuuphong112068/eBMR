<?php
use App\Http\Controllers\Pages\ManuEnv\EquipmentController;
use App\Http\Controllers\Pages\ManuEnv\RoomController;
use App\Http\Middleware\CheckLogin;
use Illuminate\Support\Facades\Route;

Route::prefix('/manu_env')
    ->name('pages.manu_env.')
    ->middleware(CheckLogin::class)
    ->group(function () {
        Route::prefix('/equipment')->name('equipment.')->controller(EquipmentController::class)->group(function () {
            Route::get('', 'index')->name('list');
            Route::post('store', 'store')->name('store');
            Route::post('update', 'update')->name('update');
            Route::post('delete', 'delete')->name('delete');
        });
        
        Route::prefix('/room')->name('room.')->controller(RoomController::class)->group(function () {
            Route::get('', 'index')->name('list');
            Route::get('get-equipments', 'getEquipments')->name('getEquipments');
            Route::post('assign-equipment', 'assignEquipment')->name('assignEquipment');
            Route::post('remove-equipment', 'removeEquipment')->name('removeEquipment');
            Route::get('get-conditions', 'getConditions')->name('getConditions');
            Route::post('store-condition', 'storeCondition')->name('storeCondition');
            Route::post('update-condition', 'updateCondition')->name('updateCondition');
            Route::post('delete-condition', 'deleteCondition')->name('deleteCondition');
            Route::get('get-related-forms', 'getRelatedForms')->name('getRelatedForms');
            Route::post('save-related-forms', 'saveRelatedForms')->name('saveRelatedForms');
        });
    });
