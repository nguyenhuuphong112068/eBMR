<?php
use Illuminate\Support\Facades\DB;

$templates = DB::table('ebmr_templates')->get();

foreach ($templates as $t) {
    echo "Fixing template ID: {$t->id} ({$t->name})\n";
    $blocks = DB::table('ebmr_template_blocks')->where('template_id', $t->id)->orderBy('order')->get();
    
    $categoryId = $t->caterogy_id ?? 0;
    $currentSectionId = ($t->type === 'BMR' || $t->type === 'BPR') ? ($categoryId . '_0') : null;

    foreach ($blocks as $b) {
        if ($b->type === 'section') {
            $prop = json_decode($b->properties);
            if (isset($prop->stage_code)) {
                $currentSectionId = $categoryId . '_' . $prop->stage_code;
            } else {
                // Try to extract from existing section_id if it's not null
                if ($b->section_id) {
                    $currentSectionId = $b->section_id;
                }
            }
        }

        if ($currentSectionId && $b->section_id !== $currentSectionId) {
            DB::table('ebmr_template_blocks')->where('id', $b->id)->update(['section_id' => $currentSectionId]);
            echo "  Updated block ID {$b->id} to section {$currentSectionId}\n";
        }
    }
}
echo "Done!\n";
