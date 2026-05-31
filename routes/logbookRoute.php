<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LogbookController;

Route::middleware(['web'])->group(function () {
    Route::get('/ebmr/logbooks/room', [LogbookController::class, 'indexRoom'])->name('pages.ebmr.logbooks.room');
    Route::get('/ebmr/logbooks/instrument', [LogbookController::class, 'indexInstrument'])->name('pages.ebmr.logbooks.instrument');
});
