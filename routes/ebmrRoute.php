<?php

use App\Http\Controllers\EbmrTemplateController;
use App\Http\Controllers\EbmrDesignerController;
use App\Http\Controllers\EbmrApprovalController;
use App\Http\Controllers\EbmrIssuanceController;
use App\Http\Controllers\EbmrExecutionController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'ebmr', 'as' => 'pages.ebmr.'], function () {
    // 1. Template Management
    Route::get('/templates', [EbmrTemplateController::class, 'index'])->name('templates');
    Route::post('/templates/metadata', [EbmrTemplateController::class, 'storeMetadata'])->name('storeTemplateMetadata');
    Route::get('/templates/{id}/data', [EbmrTemplateController::class, 'getMetadata'])->name('getTemplateMetadata');
    Route::get('/get-templates', [EbmrTemplateController::class, 'getTemplates'])->name('getTemplates');
    Route::get('/templates/{id}/blocks', [EbmrTemplateController::class, 'getTemplateBlocks'])->name('getTemplateBlocks');
    Route::get('/get-history/{id}', [EbmrTemplateController::class, 'getHistory'])->name('getHistory');
    Route::get('/templates/next-version', [EbmrTemplateController::class, 'getNextVersion'])->name('getNextVersion');

    // 2. Designer & Content
    Route::get('/designer/{id?}', [EbmrDesignerController::class, 'designer'])->name('designer');
    Route::post('/store-template', [EbmrDesignerController::class, 'save'])->name('storeTemplate');
    Route::post('/store-comment', [EbmrDesignerController::class, 'storeComment'])->name('storeComment');
    Route::post('/delete-comment', [EbmrDesignerController::class, 'deleteComment'])->name('deleteComment');
    Route::post('/translate', [EbmrDesignerController::class, 'aiTranslate'])->name('aiTranslate');
    Route::post('/translate-single', [EbmrDesignerController::class, 'aiTranslateSingle'])->name('aiTranslateSingle');


    // 3. Workflow & Approvals
    Route::get('/approvals', [EbmrApprovalController::class, 'index'])->name('approvals');
    Route::get('/templates/{id}/workflow', [EbmrApprovalController::class, 'getTemplateWorkflow'])->name('getTemplateWorkflow');
    Route::post('/templates/{id}/workflow', [EbmrApprovalController::class, 'storeTemplateWorkflow'])->name('storeTemplateWorkflow');
    Route::post('/approvals/process', [EbmrApprovalController::class, 'process'])->name('processApproval');

    // 4. Issuance
    Route::get('/issue-center', [EbmrIssuanceController::class, 'index'])->name('issueCenter');
    Route::post('/templates/issue', [EbmrIssuanceController::class, 'publish'])->name('issueTemplate');

    // 5. Execution & Records
    Route::get('/records', [EbmrExecutionController::class, 'index'])->name('indexRecords');
    Route::get('/execute/{id}', [EbmrExecutionController::class, 'execute'])->name('execute');
    Route::post('/update-record-data', [EbmrExecutionController::class, 'updateRecordData'])->name('updateRecordData');
    Route::post('/verify-password', [EbmrExecutionController::class, 'verifyPassword'])->name('verifyPassword');
});
