<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Pages\Ebmr\Logbook\LogbookController;

Route::middleware(['web'])->group(function () {
    Route::get('/ebmr/logbooks/room', [LogbookController::class, 'indexRoom'])->name('pages.ebmr.logbooks.room');
    Route::get('/ebmr/logbooks/room/{room_id}', [LogbookController::class, 'showRoom'])->name('pages.ebmr.logbooks.room.show');
    Route::get('/ebmr/logbooks/instrument', [LogbookController::class, 'indexInstrument'])->name('pages.ebmr.logbooks.instrument');
    Route::get('/ebmr/logbooks/instrument/{instrument_id}', [LogbookController::class, 'showInstrument'])->name('pages.ebmr.logbooks.instrument.show');
});
