<?php

use App\Http\Controllers\EbmrController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'ebmr', 'as' => 'pages.ebmr.'], function () {
    // Level 1: Template Metadata Management
    Route::get('/templates', [EbmrController::class, 'indexTemplates'])->name('templates');
    Route::post('/templates/metadata', [EbmrController::class, 'storeTemplateMetadata'])->name('storeTemplateMetadata');
    Route::get('/templates/{id}/data', [EbmrController::class, 'getTemplateMetadata'])->name('getTemplateMetadata');

    // Designer & Content
    Route::get('/designer/{id?}', [EbmrController::class, 'designer'])->name('designer');
    Route::get('/get-templates', [EbmrController::class, 'getTemplates'])->name('getTemplates');
    Route::get('/get-history/{id}', [EbmrController::class, 'getHistory'])->name('getHistory');
    Route::post('/store-template', [EbmrController::class, 'storeTemplate'])->name('storeTemplate');
    Route::post('/save', [EbmrController::class, 'save'])->name('save');
});
