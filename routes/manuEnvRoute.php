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
            Route::get('get-calibration-label/{code}', 'getCalibrationLabel')->where('code', '.*')->name('getCalibrationLabel');
            Route::get('get-maintenance-label/{code}', 'getMaintenanceLabel')->where('code', '.*')->name('getMaintenanceLabel');
            Route::post('get-status-batch', 'getStatusBatch')->name('getStatusBatch');
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

        Route::prefix('/cleaning-process')->name('cleaning_process.')->controller(\App\Http\Controllers\Pages\ManuEnv\CleaningProcessController::class)->group(function () {
            Route::get('/{type}/{id}/list', 'list')->name('list');
            Route::post('/{type}/{id}/list/create', 'createList')->name('createList');
            Route::post('/{type}/{list_id}/up-version', 'upVersion')->name('upVersion');
            Route::get('/{type}/{list_id}/design', 'index')->name('index');
            Route::post('/{type}/{list_id}/store', 'store')->name('store');
            Route::get('/{type}/{list_id}/workflow', 'getWorkflow')->name('getWorkflow');
            Route::post('/{type}/{list_id}/workflow', 'storeWorkflow')->name('storeWorkflow');
            Route::post('/{type}/{list_id}/effective-date', 'setEffectiveDate')->name('setEffectiveDate');
            Route::post('/upload-image', 'uploadImage')->name('upload_image');
            // Campaign routes (thực hiện vệ sinh phòng)
            Route::get('/room/{room_id}/campaign/open', 'openCampaignPage')->name('campaign.open');
            Route::get('/room/{room_id}/campaign/print', 'printCampaign')->name('campaign.print');
            Route::post('/room/{room_id}/campaign/start', 'startCampaign')->name('campaign.start');
            Route::get('/campaign/{campaign_id}', 'getCampaign')->name('campaign.get');
            Route::post('/campaign/{campaign_id}/step/{step_id}/complete', 'completeStep')->name('campaign.completeStep');
            Route::post('/campaign/{campaign_id}/complete', 'completeCampaign')->name('campaign.complete');
            // Equip campaign routes
            Route::get('/equip/{equip_id}/campaign/open', [\App\Http\Controllers\Pages\ManuEnv\CleaningEquipCampaignController::class, 'openCampaignPage'])->name('equip.campaign.open');
            Route::get('/equip/{equip_id}/campaign/print', [\App\Http\Controllers\Pages\ManuEnv\CleaningEquipCampaignController::class, 'printCampaign'])->name('equip.campaign.print');
            Route::post('/equip-campaign/{campaign_id}/step/{step_id}/complete', [\App\Http\Controllers\Pages\ManuEnv\CleaningEquipCampaignController::class, 'completeStep'])->name('equip.campaign.completeStep');
            Route::post('/equip-campaign/{campaign_id}/complete', [\App\Http\Controllers\Pages\ManuEnv\CleaningEquipCampaignController::class, 'completeCampaign'])->name('equip.campaign.complete');
            Route::get('/equip-campaign/{campaign_id}', [\App\Http\Controllers\Pages\ManuEnv\CleaningEquipCampaignController::class, 'getCampaign'])->name('equip.campaign.get');
        });

        Route::prefix('/clearance-process')->name('clearance_process.')->controller(\App\Http\Controllers\Pages\ManuEnv\ClearanceProcessController::class)->group(function () {
            Route::get('/{type}/{id}/list', 'list')->name('list');
            Route::post('/{type}/{id}/list/create', 'createList')->name('createList');
            Route::post('/{type}/{list_id}/up-version', 'upVersion')->name('upVersion');
            Route::get('/{type}/{list_id}/design', 'index')->name('index');
            Route::post('/{type}/{list_id}/store', 'store')->name('store');
            Route::get('/{type}/{list_id}/workflow', 'getWorkflow')->name('getWorkflow');
            Route::post('/{type}/{list_id}/workflow', 'storeWorkflow')->name('storeWorkflow');
            Route::post('/{type}/{list_id}/effective-date', 'setEffectiveDate')->name('setEffectiveDate');
            Route::post('/upload-image', 'uploadImage')->name('upload_image');
            
            Route::get('/room/{room_id}/campaign/open', 'openCampaignPage')->name('campaign.open');
            Route::post('/room/{room_id}/campaign/start', 'startCampaign')->name('campaign.start');
            Route::get('/campaign/{campaign_id}', 'getCampaign')->name('campaign.get');
            Route::post('/campaign/{campaign_id}/step/{step_id}/complete', 'completeStep')->name('campaign.completeStep');
            Route::post('/campaign/{campaign_id}/complete', 'completeCampaign')->name('campaign.complete');
        });

        // Room Clearing (Phòng Vệ Sinh Chung)
        Route::prefix('/room-clearing')->name('room_clearing.')->controller(\App\Http\Controllers\Pages\ManuEnv\RoomClearingController::class)->group(function () {
            Route::get('', 'index')->name('index');
            Route::get('/{id}/dashboard', 'dashboard')->name('dashboard');
            Route::post('', 'store')->name('store');
            Route::put('/{id}', 'update')->name('update');
            Route::post('/{id}/receive-equip', 'receiveEquip')->name('receiveEquip');
        });
    });

// ── API nhẹ (không cần middleware riêng, dùng chung middleware của manu_env) ──
Route::middleware(\App\Http\Middleware\CheckLogin::class)->group(function () {
    Route::get('/ebmr/api/equip-processes', function (\Illuminate\Http\Request $req) {
        $equipId = $req->query('equip_id');
        if (!$equipId) return response()->json([]);
        $processes = \App\Models\CleaningEquipProcessList::where('equipment_id', $equipId)
            ->whereIn('status', ['active', 'approved', 'submitted'])
            ->orderByRaw("FIELD(status, 'active', 'approved', 'submitted')")
            ->orderBy('version', 'desc')
            ->get(['id', 'process_name', 'version', 'status', 'cleaning_type']);
        return response()->json($processes);
    })->name('api.equip_processes');
});
