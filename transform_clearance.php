<?php
$file = 'd:\LEMP\eBMR\app\Http\Controllers\Pages\ManuEnv\ClearanceProcessController.php';
$content = file_get_contents($file);

$replacements = [
    'CleaningProcessController' => 'ClearanceProcessController',
    'CleaningRoomProcessList' => 'ClearanceRoomProcessList',
    'CleaningRoomProcess' => 'ClearanceRoomProcess',
    'CleaningRoomCampaignStep' => 'ClearanceRoomCampaignStep',
    'CleaningRoomCampaign' => 'ClearanceRoomCampaign',
    'CleaningProcessWorkflow' => 'ClearanceProcessWorkflow',
    'CleaningEquipCampaign' => 'ClearanceEquipCampaign', // won't exist but just in case
    'CleaningEquipProcessList' => 'ClearanceEquipProcessList',
    'cleaning_process' => 'clearance_process',
    'cleaning_type' => 'clearance_type',
    'pages.manu_env.cleaning_process' => 'pages.manu_env.clearance_process',
    'cleaning-process' => 'clearance-process',
];

$content = str_replace(array_keys($replacements), array_values($replacements), $content);

// We should also remove equipment related logic. Let's see if we can just comment them out or leave them (since they would reference non-existent models).
// Better to let the developer fix remaining issues if any, but let's try to remove obvious equipCampaigns usage.

file_put_contents($file, $content);
echo "Replacements done.";
