<?php

use App\Http\Controllers\Pages\Ebmr\Approvals\EbmrApprovalController;
use App\Http\Controllers\Pages\Ebmr\Designer\EbmrDesignerController;
use App\Http\Controllers\Pages\Ebmr\Records\EbmrExecutionController;
use App\Http\Controllers\Pages\Ebmr\Issuance\EbmrIssuanceController;
use App\Http\Controllers\Pages\Ebmr\Templates\EbmrTemplateController;
use Illuminate\Support\Facades\Route;
Route::group(['prefix' => 'ebmr', 'as' => 'pages.ebmr.'], function () {
    // 1. Template Management
    Route::get('/templates', [EbmrTemplateController::class, 'index'])->name('templates');
    Route::post('/templates/metadata', [EbmrTemplateController::class, 'storeMetadata'])->name('storeTemplateMetadata');
    Route::post('/templates/co-category', [EbmrTemplateController::class, 'storeCoCategory'])->name('storeCoCategory');
    Route::post('/templates/duplicate', [EbmrTemplateController::class, 'duplicateTemplate'])->name('duplicateTemplate');
    Route::post('/templates/effective-date', [EbmrTemplateController::class, 'updateEffectiveDate'])->name('updateEffectiveDate');
    // Ủy quyền chỉnh sửa & đổi Dược sĩ phụ trách
    Route::get('/templates/{id}/editors', [EbmrTemplateController::class, 'getEditors'])->name('getTemplateEditors');
    Route::post('/templates/editors/add', [EbmrTemplateController::class, 'addEditor'])->name('addTemplateEditor');
    Route::post('/templates/editors/remove', [EbmrTemplateController::class, 'removeEditor'])->name('removeTemplateEditor');
    Route::post('/templates/change-owner', [EbmrTemplateController::class, 'changeOwner'])->name('changeTemplateOwner');
    Route::post('/templates/{id}/change-reason', [EbmrTemplateController::class, 'saveChangeReason'])->name('saveChangeReason');
    Route::get('/templates/{id}/data', [EbmrTemplateController::class, 'getMetadata'])->name('getTemplateMetadata');
    Route::get('/get-templates', [EbmrTemplateController::class, 'getTemplates'])->name('getTemplates');
    Route::get('/templates/gf-by-doc-code/blocks', [EbmrTemplateController::class, 'getGfBlocksByDocCode'])->name('getGfBlocksByDocCode');
    Route::get('/templates/{id}/blocks', [EbmrTemplateController::class, 'getTemplateBlocks'])->name('getTemplateBlocks');
    Route::get('/get-history/{id}', [EbmrTemplateController::class, 'getHistory'])->name('getHistory');
    Route::get('/templates/{id}/version-diff', [EbmrTemplateController::class, 'getVersionDiff'])->name('getVersionDiff');
    Route::get('/templates/next-version', [EbmrTemplateController::class, 'getNextVersion'])->name('getNextVersion');
    Route::get('/material-info', [EbmrTemplateController::class, 'getMaterialInfo'])->name('getMaterialInfo');
    Route::get('/templates/{id}/testing-data', [EbmrTemplateController::class, 'getTestingData'])->name('getTestingData');
    Route::post('/templates/{id}/testing-data', [EbmrTemplateController::class, 'saveTestingData'])->name('saveTestingData');
    Route::post('/templates/testing-data/upload-image', [EbmrTemplateController::class, 'uploadTestingImage'])->name('uploadTestingImage');
    Route::get('/templates/{id}/rooms', [EbmrTemplateController::class, 'getRoomsConfig'])->name('getRoomsConfig');
    Route::post('/templates/{id}/rooms', [EbmrTemplateController::class, 'saveRoomsConfig'])->name('saveRoomsConfig');

    // 2. Designer & Content
    Route::get('/designer/{id?}', [EbmrDesignerController::class, 'designer'])->name('designer');
    Route::post('/store-template', [EbmrDesignerController::class, 'save'])->name('storeTemplate');
    Route::post('/store-comment', [EbmrDesignerController::class, 'storeComment'])->name('storeComment');
    Route::post('/delete-comment', [EbmrDesignerController::class, 'deleteComment'])->name('deleteComment');
    Route::post('/reply-comment', [EbmrDesignerController::class, 'replyComment'])->name('replyComment');
    Route::post('/reanchor-comment', [EbmrDesignerController::class, 'reanchorComment'])->name('reanchorComment');
    Route::post('/translate', [EbmrDesignerController::class, 'aiTranslate'])->name('aiTranslate');
    Route::post('/translate-single', [EbmrDesignerController::class, 'aiTranslateSingle'])->name('aiTranslateSingle');

    // 3. Workflow & Approvals
    Route::get('/approvals', [EbmrApprovalController::class, 'index'])->name('approvals');
    Route::get('/approvals/workflow-history/{type}/{id}', [EbmrApprovalController::class, 'getWorkflowHistory'])->name('getWorkflowHistory');
    Route::get('/templates/{id}/workflow', [EbmrApprovalController::class, 'getTemplateWorkflow'])->name('getTemplateWorkflow');
    Route::post('/templates/{id}/workflow', [EbmrApprovalController::class, 'storeTemplateWorkflow'])->name('storeTemplateWorkflow');
    Route::post('/approvals/process', [EbmrApprovalController::class, 'process'])->name('processApproval');
    Route::get('/approvals/eligible-users', [EbmrApprovalController::class, 'getEligibleUsers'])->name('getEligibleUsers');
    Route::post('/approvals/reassign', [EbmrApprovalController::class, 'reassignWorkflowUser'])->name('reassignWorkflowUser');

    // 4. Issuance
    Route::get('/issue-center', [EbmrIssuanceController::class, 'index'])->name('issueCenter');
    Route::post('/templates/issue', [EbmrIssuanceController::class, 'publish'])->name('issueTemplate');
    Route::post('/templates/check-batch', [EbmrIssuanceController::class, 'checkBatchNumber'])->name('checkBatchNumber');
    Route::get('/templates/{id}/next-batch', [EbmrIssuanceController::class, 'getSuggestion'])->name('getBatchSuggestion');

    // 5. Execution & Records
    Route::get('/records', [EbmrExecutionController::class, 'index'])->name('indexRecords');
    Route::get('/production', [EbmrExecutionController::class, 'productionIndex'])->name('production');
    Route::get('/dirty-equipments', [\App\Http\Controllers\Pages\ManuEnv\DirtyEquipmentController::class, 'index'])->name('dirty_equipments');
    Route::get('/production/bms-data', [EbmrExecutionController::class, 'getBmsData'])->name('productionBmsData');
    Route::get('/production/environment-readings/{distribution_id}', [\App\Http\Controllers\Pages\Ebmr\Logbook\ProductionEnvironmentController::class, 'readingsJson'])->name('production.environmentReadings');
    Route::get('/production/logbook-label', [EbmrExecutionController::class, 'getLogbookLabel'])->name('getLogbookLabel');
    Route::post('/production/start', [EbmrExecutionController::class, 'startProduction'])->name('startProduction');
    Route::post('/production/end', [EbmrExecutionController::class, 'endProduction'])->name('endProduction');
    Route::get('/execute/{id}', [EbmrExecutionController::class, 'execute'])->name('execute');
    Route::post('/update-record-data', [EbmrExecutionController::class, 'updateRecordData'])->name('updateRecordData');
    Route::post('/record-structure', [EbmrExecutionController::class, 'saveRecordStructure'])->name('saveRecordStructure');
    Route::post('/records/attachments', [EbmrExecutionController::class, 'uploadSectionAttachment'])->name('uploadSectionAttachment');
    Route::delete('/records/attachments/{id}', [EbmrExecutionController::class, 'deleteSectionAttachment'])->name('deleteSectionAttachment');
    Route::get('/run-data-history/{record_id}/{block_uuid}/{cell_id}', [EbmrExecutionController::class, 'getRunDataHistory'])->name('getRunDataHistory');
    Route::post('/verify-password', [EbmrExecutionController::class, 'verifyPassword'])->name('verifyPassword');
    Route::post('/verify-checker', [EbmrExecutionController::class, 'verifyChecker'])->name('verifyChecker');
    Route::get('/records/{recordId}/distribution', [EbmrExecutionController::class, 'getRecordDistribution'])->name('getRecordDistribution');
    Route::get('/records/{recordId}/distribution-history', [EbmrExecutionController::class, 'getRecordDistributionHistory'])->name('getRecordDistributionHistory');
    Route::post('/records/distribute', [EbmrExecutionController::class, 'distributeSections'])->name('distributeSections');
    Route::get('/production/room-options', [EbmrExecutionController::class, 'getRoomOptions'])->name('getRoomOptions');
    Route::get('/records/{recordId}/workshop-users', [EbmrExecutionController::class, 'getRecordWorkshopUsers'])->name('getRecordWorkshopUsers');
    Route::post('/log-error', [EbmrDesignerController::class, 'logError'])->name('logError');
    Route::post('/dynamic-options', [EbmrDesignerController::class, 'getDynamicOptions'])->name('dynamicOptions');
    Route::get('/designer-api/equipment', [EbmrDesignerController::class, 'getEquipmentList'])->name('designerEquipmentList');
    Route::get('/document/view-by-code/{code}', [EbmrExecutionController::class, 'viewDocumentByCode'])->name('viewDocumentByCode');
    Route::get('/document/check-exists/{code}', [EbmrExecutionController::class, 'checkDocumentExists'])->name('checkDocumentExists');

    // 6. Document Properties
    Route::get('/templates/{id}/properties', [EbmrTemplateController::class, 'getProperties'])->name('getProperties');
    Route::post('/templates/{id}/properties', [EbmrTemplateController::class, 'saveProperty'])->name('saveProperty');
    Route::delete('/templates/{id}/properties/{propId}', [EbmrTemplateController::class, 'deleteProperty'])->name('deleteProperty');

    // 7. MMS Integration
    Route::get('/mms/stock/{barcode}', [\App\Http\Controllers\MmsController::class, 'getStockByBarcode'])->name('mmsStockByBarcode');
});
