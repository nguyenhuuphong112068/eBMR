<?php

use App\Http\Controllers\EbmrController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'ebmr', 'as' => 'pages.ebmr.'], function () {
    Route::get('/draft', [EbmrController::class, 'draft'])->name('draft');
    Route::get('/designer', [EbmrController::class, 'designer'])->name('designer');
    Route::post('/store-template', [EbmrController::class, 'storeTemplate'])->name('storeTemplate');
    Route::post('/save', [EbmrController::class, 'save'])->name('save');
});
