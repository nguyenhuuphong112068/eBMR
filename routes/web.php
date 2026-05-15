

<?php
require_once(__DIR__ . '/generalRoute.php');
require_once(__DIR__ . '/materDataRoute.php');
require_once(__DIR__ . '/UserRoute.php');
require_once(__DIR__ . '/AuditTrialRoute.php');

require_once(__DIR__ . '/NotificationRoute.php');
require_once(__DIR__ . '/ChatRoute.php');
require_once(__DIR__ . '/ebmrRoute.php');

require_once(__DIR__ . '/categoryRoute.php');

// require_once(__DIR__ . '/ImportRoute.php');
// require_once(__DIR__ . '/SchedualRoute.php');

// require_once(__DIR__ . '/HistoryRoute.php');
// require_once(__DIR__ . '/statisticsRoute.php');
// AI Training Routes
use App\Http\Controllers\General\AiTrainingController;
Route::middleware(['web'])->group(function () {
    Route::get('/ai-training', [AiTrainingController::class, 'index'])->name('ai_training.index');
    Route::post('/ai-training/store', [AiTrainingController::class, 'storeKnowledge'])->name('ai_training.store');
    Route::delete('/ai-training/delete/{id}', [AiTrainingController::class, 'deleteKnowledge'])->name('ai_training.delete');
    Route::post('/ai-training/resolve/{id}', [AiTrainingController::class, 'resolveQuery'])->name('ai_training.resolve');
});
// require_once(__DIR__ . '/quotaRoute.php');
// require_once(__DIR__ . '/statusRoute.php');
// require_once(__DIR__ . '/quarantineRoute.php');
// require_once(__DIR__ . '/reportRoute.php');
// require_once(__DIR__ . '/AssignmentRoute.php');
// require_once(__DIR__ . '/MMSRoute.php');


// require_once(__DIR__ . '/exportExcelRoute.php');
// require_once(__DIR__ . '/n8nRoute.php');


?>

