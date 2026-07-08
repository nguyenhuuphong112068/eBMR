<?php

namespace App\Http\Controllers\Pages\Ebmr\Designer;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EbmrDesignerController extends Controller
{
    public function designer(Request $request, $id = null)
    {

        try {
            $id = $id ?? $request->query('id');
            $sectionId = $request->query('section');
            $lang = $request->query('lang', 'vi'); // vi, en, dual
            $template = null;
            $isReadOnly = false;
            $comments = [];

            if ($id) {
                $template = DB::table('ebmr_templates')
                    ->leftJoin('user_management', 'ebmr_templates.owner_id', '=', 'user_management.id')
                    ->leftJoin('designations', 'user_management.designation_id', '=', 'designations.id')
                    ->where('ebmr_templates.id', $id)
                    ->select('ebmr_templates.*', 'user_management.fullName as owner_name', 'user_management.signature_image as owner_signature', 'designations.name as owner_designation')
                    ->first();
                if ($template) {
                    // Update session title based on type
                    $type = $template->type ?? 'BMR';
                    $title = 'Thiết kế hồ sơ BMR';
                    if ($type === 'GF') {
                        $title = 'Thiết kế biểu mẫu dùng chung';
                    }
                    if ($type === 'BPR') {
                        $title = 'Thiết kế hồ sơ đóng gói';
                    }
                    if ($type === 'MF') {
                        $title = 'Thiết kế biểu mẫu gốc';
                    }
                    if ($type === 'CO') {
                        $title = 'Thiết kế Thành phần';
                    }

                    // Get category extra info for header display
                    if ($type === 'GF') {
                        $cat = DB::table('gf_category')->where('id', $template->caterogy_id)->first();
                        $template->category_code = $cat->code ?? '';
                        $template->category_name = $cat->name ?? '';
                        $template->relatived_sop_no = $cat->relatived_sop_no ?? '';
                    } elseif ($type === 'CO') {
                        $cat = DB::table('co_category')->where('id', $template->caterogy_id)->first();
                        $template->category_code = $cat->code ?? '';
                        $template->category_name = $cat->name ?? '';
                    } elseif ($type === 'MF') {
                        $cat = DB::table('mf_category')->where('id', $template->caterogy_id)->first();
                        $template->category_code = $cat->code ?? '';
                        $template->category_name = $cat->name ?? '';
                        $template->stage_name = $cat->stage_name ?? '';
                    } elseif ($type === 'BPR') {
                        $cat = DB::table('finished_product_category')
                            ->leftJoin('product_name', 'finished_product_category.product_name_id', '=', 'product_name.id')
                            ->where('finished_product_category.id', $template->caterogy_id)
                            ->select('finished_product_category.*', 'product_name.name as product_name')
                            ->first();
                        $template->category_code = $cat->finished_product_code ?? '';
                        $template->category_name = $cat->product_name ?? '';
                    } else {
                        $cat = DB::table('intermediate_category')
                            ->leftJoin('product_name', 'intermediate_category.product_name_id', '=', 'product_name.id')
                            ->leftJoin('dosage', 'intermediate_category.dosage_id', '=', 'dosage.id')
                            ->where('intermediate_category.id', $template->caterogy_id)
                            ->select('intermediate_category.*', 'product_name.name as product_name', 'dosage.name as dosage_name')
                            ->first();
                        $template->category_code = $cat->intermediate_code ?? '';
                        $template->category_name = $cat->product_name ?? '';
                        $template->dosage_name = $cat->dosage_name ?? '';
                        $template->type_name = $cat->type ?? 'Thuốc Kê Đơn'; // Default if empty
                        $template->batch_size = ($cat->batch_size ?? '').' '.($cat->unit_batch_size ?? '');
                        
                        // Bổ sung các trường phục vụ tạo tự động bảng CÔNG THỨC PHA CHẾ
                        $template->batch_qty = ($cat->batch_qty ?? '').' '.($cat->unit_batch_qty ?? '');
                        $template->raw_batch_size = (float)($cat->batch_size ?? 0);
                        $template->unit_batch_size = $cat->unit_batch_size ?? '';

                        $formulas = DB::table('formula_preparation')
                            ->where('ebmr_templates_id', $id)
                            ->orderBy('id')
                            ->get();

                        foreach ($formulas as $formula) {
                            $formula->materials = DB::table('formula_materials')
                                ->where('preparation_formula_id', $formula->id)
                                ->orderBy('id')
                                ->get();
                                
                            $formula->sub_amounts = DB::table('formula_ingredient_amount')
                                ->where('preparation_formula_id', $formula->id)
                                ->orderBy('id')
                                ->get();
                        }
                        $template->formulas = $formulas;
                    }

                    session(['title' => $title . ' - ' . ($template->category_name ?? '')]);

                    // Determine read-only state
                    $currentUserId = session('user')['userId'] ?? null;
                    $isAdmin = (session('user')['userGroup'] ?? '') === 'Admin';
                    // Read-only nếu: không phải owner, hoặc không ở trạng thái draft
                    // Admin luôn có thể xem (read-only) nhưng không bị redirect
                    if (!$currentUserId || ($template->owner_id != $currentUserId && !$isAdmin) || $template->status !== 'draft') {
                        $isReadOnly = true;
                    }

                    // Recalculation block auto-creation if is_recalculation == 1
                    if ($template->is_recalculation == 1 && $template->type === 'BMR') {
                        $recalcSectionId = $template->caterogy_id . '_recalc';
                        $hasRecalc = DB::table('ebmr_template_blocks')
                            ->where('template_id', $id)
                            ->where('section_id', $recalcSectionId)
                            ->exists();
                        if (!$hasRecalc) {
                            DB::table('ebmr_template_blocks')
                                ->where('template_id', $id)
                                ->increment('order', 2);

                            $sectionBlockId = DB::table('ebmr_template_blocks')->insertGetId([
                                'template_id' => $id,
                                'section_id' => $recalcSectionId,
                                'type' => 'section',
                                'label' => 'section_recalc',
                                'order' => 0,
                                'properties' => json_encode([
                                    'id' => 'blk_sec_recalc_' . uniqid(),
                                    'type' => 'section',
                                    'label' => 'TÍNH TOÁN CÔNG THỨC',
                                    'stage_code' => 'recalc',
                                    'section_id' => $recalcSectionId
                                ]),
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);

                            $recalcBlockId = DB::table('ebmr_template_blocks')->insertGetId([
                                'template_id' => $id,
                                'section_id' => $recalcSectionId,
                                'type' => 'static-text',
                                'label' => 'TÍNH TOÁN CÔNG THỨC BLOCK',
                                'order' => 1,
                                'properties' => json_encode([
                                    'id' => 'blk_recalc_' . uniqid(),
                                    'type' => 'static-text',
                                    'label' => 'TÍNH TOÁN CÔNG THỨC BLOCK',
                                    'isCalculationBlock' => true,
                                    'section_id' => $recalcSectionId
                                ]),
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);

                            $defaultContent = '<p>Nguyên liệu trong công thức được tính trên dạng nguyên trạng, lượng nguyên liệu thực tế sử dụng cho mỗi mẻ được tính dựa trên hàm lượng nguyên trạng từng lô nguyên liệu.</p>';
                            
                            $contentId = DB::table('ebmr_content_blocks')->insertGetId([
                                'ebmr_template_blocks_id' => $recalcBlockId,
                                'template_id' => $id,
                                'section_id' => $recalcSectionId,
                                'type' => 'static-text',
                                'vi_contents' => $defaultContent,
                                'en_contents' => $defaultContent,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);

                            $placeholder = "[[CONTENT_$contentId]]";
                            DB::table('ebmr_template_blocks')->where('id', $recalcBlockId)->update([
                                'content' => '<div class="static-text-display">' . $placeholder . '</div>'
                            ]);
                        }
                    }

                    $blocks = DB::table('ebmr_template_blocks')->where('template_id', $id)->orderBy('order')->get();

                    // Fetch all testing criteria and properties for dynamic reference replacement
                    $templateIds = [$id];
                    foreach ($blocks as $block) {
                        $f = json_decode($block->properties, true);
                        if (isset($f['type']) && $f['type'] === 'linked-template') {
                            $ltId = $f['template_id'] ?? null;
                            if ($ltId) {
                                $templateIds[] = $ltId;
                            }
                        }
                    }
                    $testingCriteria = DB::table('testing')->whereIn('ebmr_templace_id', $templateIds)->get()->keyBy('id');
                    $properties = DB::table('ebmr_properties')->whereIn('ebmr_templace_id', $templateIds)->get()->keyBy('name');

                    // Fetch all content blocks for this template's blocks to minimize queries
                    $blockIds = $blocks->pluck('id')->toArray();
                    $contentBlocks = DB::table('ebmr_content_blocks')
                        ->whereIn('ebmr_template_blocks_id', $blockIds)
                        ->get()
                        ->groupBy('ebmr_template_blocks_id');

                    $fields = [];
                    $fieldsConfig = new \stdClass;

                    // Load fieldsConfig from the new dedicated table (One row per variable)
                    $variants = DB::table('ebmr_variants')->where('template_id', $id)->get();
                    if ($variants->isNotEmpty()) {
                        foreach ($variants as $v) {
                            $config = json_decode($v->config, true) ?? [];
                            $fieldsConfig->{$v->field_key} = array_merge([
                                'id' => $v->field_key,
                                'name' => $v->name,
                                'label' => $v->label,
                                'type' => $v->type,
                                'important_var_id' => $v->important_var_id,
                                'section_id' => $v->section_id,
                                'block_id' => $v->block_id,
                            ], $config);
                        }
                    } elseif ($blocks->isNotEmpty()) {
                        // Fallback: Check if it's still in the first block (for legacy data)
                        $fieldsConfig = json_decode($blocks->first()->fields_config);
                    }

                    // Override fieldsConfig with testing criteria dynamically
                    $fieldsConfigArr = (array)$fieldsConfig;
                    $fieldsConfigArr = $this->overrideFieldsConfigWithTesting($fieldsConfigArr, $testingCriteria);
                    $fieldsConfig = (object)$fieldsConfigArr;

                    if ($blocks->isNotEmpty()) {
                        $allFields = [];
                        $categoryId = $template->caterogy_id ?? 0;
                        $currentSectionId = ($template->type === 'BMR' || $template->type === 'BPR') ? ($categoryId.'_0') : null;

                        foreach ($blocks as $block) {
                            $f = json_decode($block->properties, true); // Decode as array to modify easily

                            // Inject content back from ebmr_content_blocks
                            $this->injectContent($f, $block, $contentBlocks->get($block->id), $lang, $testingCriteria, $properties);

                            // Ensure block has a unique ID for frontend tracking
                            if (! isset($f['id'])) {
                                $f['id'] = 'blk_'.$block->id.'_'.uniqid();
                                // Update DB to persist this ID
                                DB::table('ebmr_template_blocks')->where('id', $block->id)->update(['properties' => json_encode($f)]);
                            }

                            $f['db_id'] = $block->id;

                            // Auto-repair section_id if it's missing but we can determine it
                            if (isset($f['type']) && $f['type'] === 'section' && ! empty($f['stage_code'])) {
                                $currentSectionId = $categoryId.'_'.$f['stage_code'];
                            }

                            // If DB has NULL but we have a current section, fix it in memory for the response
                            if (empty($block->section_id) && $currentSectionId) {
                                $block->section_id = $currentSectionId;
                                DB::table('ebmr_template_blocks')->where('id', $block->id)->update(['section_id' => $currentSectionId]);
                            }

                            // If we are filtering by section, only add if it matches OR if it's the header
                            // Determine if this block is a "Header" (should be visible in all sections)
                            $isHeader = ($block->section_id == $template->caterogy_id) || 
                                        ($f['isBmrHeader'] ?? false) || 
                                        ($f['isGfHeader'] ?? false) || 
                                        (($f['type'] ?? '') === 'section' && ($f['locked'] ?? false)) ||
                                        ($f['isAbbreviationTable'] ?? false);

                            if (! $sectionId || $block->section_id == $sectionId || $isHeader) {
                                $f['section_id'] = $block->section_id; // Ensure section_id is in property for sorting
                                $f['block_order'] = $block->order;
                                $fields[] = (object) $f;
                            }
                        }

                        // Sort fields globally: first by section code, then by original block order
                        usort($fields, function ($a, $b) {
                            $sidA = $a->section_id ?? '';
                            $sidB = $b->section_id ?? '';

                            // Header (exact category ID) always comes first
                            if ($sidA != $sidB) {
                                $partsA = explode('_', $sidA);
                                $partsB = explode('_', $sidB);
                                
                                // If it's the base category ID (no underscore), treat as code -1 to be first
                                $codeA = (count($partsA) > 1) ? (int) end($partsA) : -1;
                                $codeB = (count($partsB) > 1) ? (int) end($partsB) : -1;

                                if ($codeA !== $codeB) {
                                    return $codeA <=> $codeB;
                                }
                            }

                            return ($a->block_order ?? 0) <=> ($b->block_order ?? 0);
                        });
                    }
                    $template->schema = (object) ['fields' => $fields, 'fieldsConfig' => $fieldsConfig];

                    // Load comments
                    $comments = DB::table('ebmr_template_comments')
                        ->leftJoin('user_management', 'ebmr_template_comments.user_id', '=', 'user_management.id')
                        ->where('template_id', $id)
                        ->select('ebmr_template_comments.*', 'user_management.fullName as user_name')
                        ->orderBy('created_at', 'asc')
                        ->get();

                    $importantVars = DB::table('important_var')->get();

                    $workflows = DB::table('ebmr_template_workflows')
                        ->leftJoin('user_management', 'ebmr_template_workflows.user_id', '=', 'user_management.id')
                        ->leftJoin('designations', 'user_management.designation_id', '=', 'designations.id')
                        ->where('template_id', $id)
                        ->orderBy('step_order')
                        ->select('ebmr_template_workflows.*', 'user_management.fullName', 'user_management.groupName as title', 'user_management.deparment as department_name', 'user_management.signature_image as signature_image', 'designations.name as designation_name')
                        ->get();
                    $template->workflows = $workflows;

                    // Pilot trình soạn thảo mới (ProseMirror/TipTap) — chạy song song,
                    // dùng chung toàn bộ dữ liệu đã nạp ở trên. Vào bằng ?editor=v2.
                    $viewName = $request->query('editor') === 'v2' ? 'pages.ebmr.designer_v2' : 'pages.ebmr.designer';

                    return view($viewName, [
                        'template' => $template,
                        'isReadOnly' => ($lang === 'dual') ? true : $isReadOnly,
                        'comments' => $comments,
                        'activeSectionId' => $sectionId,
                        'lang' => $lang,
                        'importantVars' => $importantVars,
                        'isAdmin' => (session('user')['userGroup'] ?? '') === 'Admin',
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Designer error: '.$e->getMessage().' | File: '.$e->getFile().' | Line: '.$e->getLine());
            return redirect()->back()->with('error', 'Không thể mở hồ sơ. Lỗi hệ thống: '.$e->getMessage());
        }

        return redirect()->back()->with('error', 'Không tìm thấy hồ sơ hoặc bạn không có quyền truy cập.');
    }

    public function save(Request $request)
    {
        try {
            $validated = $request->validate([
                'id' => 'nullable|integer',
                'schema' => 'required|array',
                'log_history' => 'nullable|boolean',
                'lang' => 'nullable|string',
            ]);

            $lang = $validated['lang'] ?? 'vi';
            $schemaData = $validated['schema'];
            $fields = $schemaData['fields'] ?? [];
            $fieldsConfig = $schemaData['fieldsConfig'] ?? null;
            $sectionId = $request->section_id;
            $isIncremental = $schemaData['incremental'] ?? false;
            $deletedIds = $schemaData['deleted_ids'] ?? [];
            $deletedFieldKeys = $schemaData['deleted_field_keys'] ?? [];
            $blockOrder = $schemaData['block_order'] ?? [];

            $data = [
                'updated_at' => now(),
            ];

            if (isset($validated['log_history'])) {
                $data['log_history'] = $validated['log_history'];
            }

            if (! empty($validated['id'])) {
                $oldTemplateId = $validated['id'];
                $oldTemplateRecord = DB::table('ebmr_templates')->where('id', $oldTemplateId)->first();

                if ($oldTemplateRecord) {
                    $oldTemplate = clone $oldTemplateRecord;

                    // Reconstruct old schema for logging (Note: This might be slow if we do it every time for 1000 blocks)
                    // For incremental, we might want to skip logRevision or make it smarter.
                    // For now, let's only log if NOT incremental or if specifically needed.
                    if (! $isIncremental && ! empty($oldTemplate->log_history)) {
                        $oldBlocks = DB::table('ebmr_template_blocks')->where('template_id', $oldTemplateId)->get();
                        $oldFields = [];
                        foreach ($oldBlocks as $ob) {
                            $oldFields[] = json_decode($ob->properties, true);
                        }
                        $oldTemplate->schema = json_encode(['fields' => $oldFields]);
                        $this->logRevision($oldTemplate, $schemaData);
                    }

                    // Preserve existing translations in memory for lookup
                    $existingContent = DB::table('ebmr_content_blocks')
                        ->where('template_id', $oldTemplateId)
                        ->get()
                        ->keyBy('id');

                    DB::table('ebmr_templates')->where('id', $oldTemplateId)->update($data);
                    $id = $oldTemplateId;
                } else {
                    return response()->json(['success' => false, 'message' => 'Không tìm thấy hồ sơ gốc'], 404);
                }
            } else {
                $existingContent = collect();
                $data['created_at'] = now();
                $id = DB::table('ebmr_templates')->insertGetId($data);
            }

            // --- 2. EXTRACT ABBREVIATION TABLE ---
            $abbrevData = null;
            $abbrevDbId = null;
            $filteredFields = [];
            foreach ($fields as $field) {
                if (!empty($field['isAbbreviationTable'])) {
                    $abbrevData = $field;
                    if (!empty($field['db_id'])) {
                        $abbrevDbId = $field['db_id'];
                    }
                } else {
                    $filteredFields[] = $field;
                }
            }
            $fields = $filteredFields;
            
            if ($abbrevData) {
                DB::table('ebmr_templates')->where('id', $id)->update([
                    'abbreviations_List' => json_encode($abbrevData)
                ]);
            }
            if ($abbrevDbId && !in_array($abbrevDbId, $deletedIds)) {
                $deletedIds[] = $abbrevDbId;
            }

            // --- 2b. DOCUMENT PROPERTY (danh mục key/value tự định nghĩa, giống Word) ---
            if (array_key_exists('docProperties', $schemaData)) {
                DB::table('ebmr_templates')->where('id', $id)->update([
                    'doc_properties' => json_encode($schemaData['docProperties'] ?? (object) []),
                ]);
            }

            // --- 3. HANDLE DELETIONS ---
            if ($isIncremental && ! empty($deletedIds)) {
                DB::table('ebmr_template_blocks')->whereIn('id', $deletedIds)->delete();
                DB::table('ebmr_content_blocks')->whereIn('ebmr_template_blocks_id', $deletedIds)->delete();
            }

            // --- 4. INSERT/UPDATE DIRTY BLOCKS ---
            $templateRecord = DB::table('ebmr_templates')->where('id', $id)->first();
            $categoryId = $templateRecord->caterogy_id ?? 0;
            $currentSectionId = ($templateRecord->type === 'BMR' || $templateRecord->type === 'BPR') ? ($categoryId.'_0') : null;

            $touchedBlockIds = [];
            $touchedContentIds = [];
            $frontendToDbMap = [];

            foreach ($fields as $field) {
                $type = $field['type'] ?? 'unknown';
                $frontendId = $field['id'] ?? null;

                if ($type === 'section') {
                    $stageCode = $field['stage_code'] ?? 'USER_DEFINED_'.rand(100, 999);
                    // Ưu tiên section_id THẬT client gửi lên (có thể mang hậu tố nhánh phòng,
                    // vd "{cat}_{track}_{stageCode}") — chỉ reconstruct kiểu cũ khi client không
                    // gửi kèm, để không làm lệch section_id fallback của các field theo sau section
                    // này về nhánh GỐC (không track) khi section hiện tại là 1 nhánh đã tách.
                    $currentSectionId = $field['section_id'] ?: ($categoryId.'_'.$stageCode);
                }

                $blockDbId = $field['db_id'] ?? null;

                // Determine the correct section_id
                $finalSectionId = $field['section_id'];
                if (! $finalSectionId) {
                    if ($isIncremental && $blockDbId) {
                        // For incremental updates, if section_id is missing from frontend, keep existing one from DB
                        $existingBlock = DB::table('ebmr_template_blocks')->where('id', $blockDbId)->first();
                        $finalSectionId = $existingBlock->section_id ?? ($sectionId ?: $currentSectionId);
                    } else {
                        $finalSectionId = $sectionId ?: $currentSectionId;
                    }
                }

                // SECURITY/INTEGRITY FIX: If the frontend sends a frontend ID (blk_sec_...) for section_id,
                // we MUST resolve it to the real section_id (e.g. 6_1) to prevent cascading corruption.
                if ($finalSectionId && strpos($finalSectionId, 'blk_sec_') === 0) {
                    $secBlock = DB::table('ebmr_template_blocks')
                        ->where('template_id', $id)
                        ->where('properties', 'LIKE', '%"id":"' . $finalSectionId . '"%')
                        ->first();
                    
                    if ($secBlock && $secBlock->section_id && strpos($secBlock->section_id, 'blk_sec_') !== 0) {
                        $finalSectionId = $secBlock->section_id;
                        $field['section_id'] = $finalSectionId;
                    }
                }

                $properties = $field;

                // Process table data separately to avoid saving large text in 'properties' column
                if ($type === 'table' && isset($properties['data'])) {
                    foreach ($properties['data'] as $r => $row) {
                        foreach ($row as $c => $cell) {
                            if (is_array($properties['data'][$r][$c])) {
                                $properties['data'][$r][$c]['content'] = '';
                            } else {
                                $properties['data'][$r][$c] = ['content' => ''];
                            }
                        }
                    }
                }

                $blockDbId = $field['db_id'] ?? null;
                $blockData = [
                    'template_id' => $id,
                    'section_id' => $finalSectionId,
                    'type' => $type,
                    'label' => $frontendId,
                    'properties' => json_encode($properties),
                    'updated_at' => now(),
                ];

                // If not incremental, we handle 'order' here. If incremental, we'll do it later in a batch.
                if (! $isIncremental) {
                    $blockData['order'] = array_search($frontendId, $blockOrder) ?: 0;
                }

                // CHỐNG NHÂN ĐÔI BLOCK (idempotent): client có thể gửi lại block CHƯA kịp nhận
                // db_id (double-submit, mất kết nối sau khi server đã ghi, retry...). label chính là
                // id phía frontend (blk_v2_..., duy nhất trong template) — nếu đã tồn tại block cùng
                // label thì UPDATE bản ghi cũ thay vì INSERT thêm một bản sao.
                if (! $blockDbId && $frontendId) {
                    $existedByLabel = DB::table('ebmr_template_blocks')
                        ->where('template_id', $id)
                        ->where('label', $frontendId)
                        ->orderBy('id')
                        ->first();
                    if ($existedByLabel) {
                        $blockDbId = $existedByLabel->id;
                    }
                }

                if ($blockDbId && DB::table('ebmr_template_blocks')->where('id', $blockDbId)->exists()) {
                    DB::table('ebmr_template_blocks')->where('id', $blockDbId)->update($blockData);
                    $blockId = $blockDbId;
                } else {
                    $blockData['created_at'] = now();
                    $blockId = DB::table('ebmr_template_blocks')->insertGetId($blockData);
                }

                if ($frontendId) {
                    $frontendToDbMap[$frontendId] = ['db_id' => $blockId];
                }
                $touchedBlockIds[] = $blockId;

                // 2. Save content blocks and build HTML structure
                $htmlStructure = '';
                if ($type === 'static-text') {
                    $rawHtml = $field['content'] ?? '';
                    $contentDbId = $field['content_db_id'] ?? null;
                    $preserved = $contentDbId ? $existingContent->get($contentDbId) : null;

                    $contentData = [
                        'ebmr_template_blocks_id' => $blockId,
                        'template_id' => $id,
                        'section_id' => $finalSectionId,
                        'type' => $type,
                        'vi_contents' => $preserved->vi_contents ?? '',
                        'en_contents' => $preserved->en_contents ?? '',
                        'updated_at' => now(),
                    ];

                    if ($contentDbId && DB::table('ebmr_content_blocks')->where('id', $contentDbId)->exists()) {
                        DB::table('ebmr_content_blocks')->where('id', $contentDbId)->update($contentData);
                        $contentId = $contentDbId;
                    } else {
                        $contentData['created_at'] = now();
                        $contentId = DB::table('ebmr_content_blocks')->insertGetId($contentData);
                    }
                    $touchedContentIds[] = $contentId;

                    $placeholder = "[[CONTENT_$contentId]]";
                    [$structure, $cleanText] = $this->splitHtmlAndText($rawHtml, $placeholder);

                    $updateCol = ($lang === 'en') ? 'en_contents' : (($lang === 'vi') ? 'vi_contents' : null);
                    if ($updateCol) {
                        DB::table('ebmr_content_blocks')->where('id', $contentId)->update([$updateCol => $cleanText]);
                    }
                    $htmlStructure = '<div class="static-text-display">'.$structure.'</div>';

                    if ($frontendId) {
                        $frontendToDbMap[$frontendId]['content_db_id'] = $contentId;
                    }
                } elseif ($type === 'table') {
                    $tableResult = $this->generateTableHtmlStructure($field, $blockId, $id, $finalSectionId, $lang, $existingContent);
                    $htmlStructure = $tableResult['html'];
                    $touchedContentIds = array_merge($touchedContentIds, $tableResult['touchedIds']);

                    // Update properties with the IDs we just generated/matched
                    $properties = $tableResult['updatedField'];
                    DB::table('ebmr_template_blocks')->where('id', $blockId)->update(['properties' => json_encode($properties)]);

                    if ($frontendId) {
                        $frontendToDbMap[$frontendId]['data'] = $properties['data'];
                        // Also sync the section_id if it was updated by backend (stage code generation)
                        $frontendToDbMap[$frontendId]['section_id'] = $finalSectionId;
                    }
                } elseif ($type === 'section') {
                    if ($frontendId) {
                        $frontendToDbMap[$frontendId]['section_id'] = $finalSectionId;
                        // For section blocks, the label might need to be synced if it was used for order
                        $frontendToDbMap[$frontendId]['db_id'] = $blockId;
                    }
                } elseif ($type === 'signature') {
                    $htmlStructure = '<div class="block-signature-placeholder text-muted py-5 text-center border rounded bg-light">
                                        <i class="fas fa-pen-nib me-2"></i>'.($field['label'] ?? 'Ký xác nhận').'
                                      </div>';
                } elseif ($type === 'linked-template') {
                    $htmlStructure = '<div class="block-linked-placeholder py-4 px-3 border border-primary border-dashed rounded bg-light text-center">
                                        <i class="fas fa-link me-2 text-primary"></i> <strong>Biểu mẫu chung: '.($field['label'] ?? 'Chưa đặt tên').'</strong>
                                      </div>';
                }

                DB::table('ebmr_template_blocks')->where('id', $blockId)->update(['content' => $htmlStructure]);
            }

            // --- 3. FINAL SYNC: Ensure fields_config is in the ebmr_variants table ---
            if ($fieldsConfig) {
                // Ensure we have a complete map of Frontend UUID -> DB ID for the whole template
                // (including blocks that weren't updated in this incremental save)
                $allExistingBlocks = DB::table('ebmr_template_blocks')
                    ->where('template_id', $id)
                    ->get();

                foreach ($allExistingBlocks as $eb) {
                    $props = json_decode($eb->properties, true);
                    $fId = $props['id'] ?? null;
                    if ($fId && ! isset($frontendToDbMap[$fId])) {
                        $frontendToDbMap[$fId] = ['db_id' => $eb->id];
                    }
                }

                $keepFieldKeys = array_keys((array) $fieldsConfig);

                // HIỆU NĂNG: tài liệu có thể có hàng trăm biến số (template #50 hiện có >250).
                // Cách cũ chạy 1 SELECT + 1 INSERT/UPDATE RIÊNG cho TỪNG biến (2 lượt round-trip
                // DB mỗi biến, mỗi lượt lại tự commit) -> hàng trăm/nghìn round-trip tuần tự làm
                // "Lưu" rất chậm. Gộp lại: fetch 1 lần toàn bộ biến đã có, rồi bọc phần
                // xoá/thêm/sửa trong 1 transaction DUY NHẤT (1 lần commit thay vì hàng trăm lần).
                DB::transaction(function () use ($fieldsConfig, $id, $deletedFieldKeys, $frontendToDbMap, $keepFieldKeys) {
                    // Xoá TƯỜNG MINH các biến người dùng đã thật sự xoá (deleteVariablesV2 ở client,
                    // gửi qua deleted_field_keys — cùng mô hình với deleted_ids của block).
                    // TRƯỚC ĐÂY suy luận "biến vắng mặt trong fieldsConfig của lượt lưu NÀY = mồ côi,
                    // xoá luôn" — cách này xoá NHẦM biến vừa được dán/tạo ở 1 tab/lượt lưu KHÁC nếu
                    // ảnh chụp dữ liệu (fieldsConfig) phía client gửi lên lượt sau lại cũ hơn (race
                    // condition khi 2 lượt lưu gần nhau, kể cả cùng tab lẫn nhiều tab).
                    if (! empty($deletedFieldKeys)) {
                        DB::table('ebmr_variants')
                            ->where('template_id', $id)
                            ->whereIn('field_key', array_map('strval', $deletedFieldKeys))
                            ->delete();
                    }

                    $existingVariants = DB::table('ebmr_variants')
                        ->where('template_id', $id)
                        ->whereIn('field_key', $keepFieldKeys)
                        ->get()
                        ->keyBy('field_key');

                    $toInsert = [];
                    foreach ($fieldsConfig as $fieldKey => $config) {
                        $configArr = (array) $config;

                        // Trích xuất các trường cốt lõi, giữ các phần cấu hình còn lại trong JSON 'config'
                        $name = $configArr['name'] ?? null;
                        $label = $configArr['label'] ?? null;
                        $type = $configArr['type'] ?? 'text';
                        $vSectionId = $configArr['section_id'] ?? null;
                        $vBlockUuid = $configArr['block_id'] ?? null;
                        $importantVarId = $configArr['important_var_id'] ?? null;

                        // Ánh xạ Frontend UUID sang DB ID
                        $vBlockId = null;
                        if ($vBlockUuid && isset($frontendToDbMap[$vBlockUuid])) {
                            $vBlockId = is_array($frontendToDbMap[$vBlockUuid])
                                ? ($frontendToDbMap[$vBlockUuid]['db_id'] ?? null)
                                : $frontendToDbMap[$vBlockUuid];
                        }

                        // Loại bỏ các trường cốt lõi khỏi cấu hình JSON để tránh dư thừa
                        unset($configArr['id'], $configArr['name'], $configArr['label'], $configArr['type'], $configArr['section_id'], $configArr['block_id'], $configArr['important_var_id']);

                        $data = [
                            'template_id' => $id,
                            'field_key' => (string) $fieldKey,
                            'name' => (string) $name,
                            'label' => (string) $label,
                            'type' => (string) $type,
                            'important_var_id' => $importantVarId ? (int) $importantVarId : null,
                            'section_id' => $vSectionId ? (string) $vSectionId : null,
                            'block_id' => $vBlockId,
                            'config' => json_encode($configArr),
                            'updated_at' => now()->toDateTimeString(),
                        ];

                        $existingVariant = $existingVariants->get((string) $fieldKey);
                        if ($existingVariant) {
                            DB::table('ebmr_variants')
                                ->where('id', $existingVariant->id)
                                ->update($data);
                        } else {
                            $data['created_at'] = now()->toDateTimeString();
                            $toInsert[] = $data;
                        }
                    }

                    if (! empty($toInsert)) {
                        try {
                            DB::table('ebmr_variants')->insert($toInsert);
                        } catch (\Exception $e) {
                            \Log::error('Error inserting variants: '.$e->getMessage(), ['count' => count($toInsert)]);
                            throw $e;
                        }
                    }
                });

                // XÁC MINH: mọi biến client gửi lên phải THẬT SỰ tồn tại trong DB sau khi lưu.
                // Không có bước này thì nếu 1 biến vì lý do gì đó không ghi được, request vẫn
                // chạy hết tới response "success" phía dưới — người dùng thấy "Lưu thành công"
                // nhưng biến (vd. biến vừa dán/nhân bản) lại biến mất khi tải lại trang.
                $savedKeys = DB::table('ebmr_variants')
                    ->where('template_id', $id)
                    ->whereIn('field_key', $keepFieldKeys)
                    ->pluck('field_key')
                    ->all();
                $missingKeys = array_diff($keepFieldKeys, $savedKeys);
                if (! empty($missingKeys)) {
                    throw new \Exception('Không thể lưu '.count($missingKeys).' biến số (field_key: '.implode(', ', $missingKeys).')');
                }
            }

            // --- 4. HANDLE ORPHANS (Full save mode only) ---
            if (! $isIncremental && $id) {
                $deleteQueryBlocks = DB::table('ebmr_template_blocks')->where('template_id', $id);
                $deleteQueryContent = DB::table('ebmr_content_blocks')->where('template_id', $id);

                if ($sectionId) {
                    $deleteQueryBlocks->where('section_id', $sectionId);
                    $deleteQueryContent->where('section_id', $sectionId);
                }

                $deleteQueryBlocks->whereNotIn('id', $touchedBlockIds)->delete();
                $deleteQueryContent->whereNotIn('id', $touchedContentIds)->delete();
            }

            // --- 5. BATCH UPDATE ORDER ---
            if (! empty($blockOrder)) {
                // Chunk to avoid database parameter limits (e.g., SQL Server limit is 2100)
                $chunks = array_chunk($blockOrder, 500);
                foreach ($chunks as $chunk) {
                    $cases = [];
                    $ids = [];
                    $params = [];
                    foreach ($chunk as $index => $fId) {
                        // Calculate global index
                        $globalIndex = array_search($fId, $blockOrder);
                        $cases[] = 'WHEN label = ? THEN ?';
                        $params[] = $fId;
                        $params[] = $globalIndex;
                        $ids[] = $fId;
                    }

                    if (! empty($cases)) {
                        $caseString = implode(' ', $cases);
                        $idString = implode(',', array_fill(0, count($ids), '?'));
                        $allParams = array_merge($params, [$id], $ids);

                        DB::update("UPDATE ebmr_template_blocks SET `order` = CASE $caseString END WHERE template_id = ? AND label IN ($idString)", $allParams);
                    }
                }
            }

            Log::info("Incremental save successful for Template #$id. Dirty blocks: ".count($fields).', Deleted: '.count($deletedIds));

            return response()->json([
                'success' => true,
                'message' => 'Lưu hồ sơ thành công',
                'id' => $id,
                'block_ids' => $frontendToDbMap,
            ]);
        } catch (\Exception $e) {
            Log::error('EbmrDesignerController@save error: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Lỗi hệ thống: '.$e->getMessage()], 500);
        }
    }

    private function logRevision($oldTemplate, $newSchema)
    {
        $oldSchema = json_decode($oldTemplate->schema, true);
        $oldFields = $oldSchema['fields'] ?? [];
        $newFields = $newSchema['fields'] ?? [];

        $added = [];
        $deleted = [];
        $modified = [];
        $oldMap = [];
        foreach ($oldFields as $f) {
            $oldMap[$f['id']] = $f;
        }
        $newMap = [];
        foreach ($newFields as $f) {
            $newMap[$f['id']] = $f;
        }

        foreach ($newFields as $nf) {
            if (! isset($oldMap[$nf['id']])) {
                $added[] = $nf['label'] ?: $nf['type'];
            } else {
                $of = $oldMap[$nf['id']];
                $isDiff = (($of['label'] ?? null) !== ($nf['label'] ?? null)) ||
                    (($of['content'] ?? null) !== ($nf['content'] ?? null)) ||
                    (($of['type'] ?? null) !== ($nf['type'] ?? null)) ||
                    (($of['rows'] ?? 0) !== ($nf['rows'] ?? 0)) ||
                    (($of['cols'] ?? 0) !== ($nf['cols'] ?? 0));

                if ($isDiff) {
                    $modified[] = $nf['label'] ?: $nf['type'];
                }
            }
        }
        foreach ($oldFields as $of) {
            if (! isset($newMap[$of['id']])) {
                $deleted[] = $of['label'] ?: $of['type'];
            }
        }

        if (empty($added) && empty($deleted) && empty($modified)) {
            return;
        }

        $summaryParts = [];
        if (! empty($added)) {
            $summaryParts[] = 'Thêm: '.implode(', ', $added);
        }
        if (! empty($deleted)) {
            $summaryParts[] = 'Xóa: '.implode(', ', $deleted);
        }
        if (! empty($modified)) {
            $summaryParts[] = 'Điều chỉnh: '.implode(', ', $modified);
        }

        DB::table('ebmr_revision_history')->insert([
            'template_id' => $oldTemplate->id,
            'user_id' => session('user')['userId'] ?? null,
            'change_summary' => implode(' | ', $summaryParts),
            'details' => json_encode(['added' => $added, 'deleted' => $deleted, 'modified' => $modified]),
            'created_at' => now(),
        ]);
    }

    public function storeComment(Request $request)
    {
        $validated = $request->validate([
            'template_id' => 'required|integer',
            'content' => 'required|string',
            'selection_id' => 'nullable|string',
            'selection_data' => 'nullable|array',
        ]);

        $id = DB::table('ebmr_template_comments')->insertGetId([
            'template_id' => $validated['template_id'],
            'user_id' => session('user')['userId'],
            'content' => $validated['content'],
            'selection_id' => $validated['selection_id'],
            'selection_data' => isset($validated['selection_data']) ? json_encode($validated['selection_data']) : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = DB::table('user_management')->where('id', session('user')['userId'])->first();

        return response()->json([
            'success' => true,
            'comment' => [
                'id' => $id,
                'content' => $validated['content'],
                'user_name' => $user->fullName,
                'created_at' => now()->format('Y-m-d H:i:s'),
                'selection_id' => $validated['selection_id'],
            ],
        ]);
    }

    public function deleteComment(Request $request)
    {
        $currentUserId = session('user')['userId'] ?? null;
        $isAdmin = (session('user')['userGroup'] ?? '') === 'Admin';

        $comment = DB::table('ebmr_template_comments')->where('id', $request->id)->first();

        if (!$comment) {
            return response()->json(['success' => false, 'message' => 'Bình luận không tồn tại.'], 404);
        }

        // Chỉ cho phép xóa nếu là chủ sở hữu comment hoặc Admin
        if (!$isAdmin && $comment->user_id != $currentUserId) {
            return response()->json(['success' => false, 'message' => 'Bạn không có quyền xóa bình luận này.'], 403);
        }

        DB::table('ebmr_template_comments')->where('id', $request->id)->delete();

        return response()->json(['success' => true]);
    }

    public function replyComment(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer',
            'content' => 'required|string',
        ]);

        $comment = DB::table('ebmr_template_comments')->where('id', $validated['id'])->first();
        if (!$comment) {
            return response()->json(['success' => false, 'message' => 'Bình luận không tồn tại.']);
        }

        $user = DB::table('user_management')->where('id', session('user')['userId'])->first();
        $userName = $user ? $user->fullName : 'Unknown User';

        // Parse existing content
        $contentData = json_decode($comment->content, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($contentData)) {
            $contentData = [
                'text' => $comment->content,
                'replies' => []
            ];
        }

        // Add new reply
        $reply = [
            'user_name' => $userName,
            'content' => $validated['content'],
            'created_at' => now()->format('Y-m-d H:i:s'),
        ];

        $contentData['replies'][] = $reply;

        DB::table('ebmr_template_comments')->where('id', $validated['id'])->update([
            'content' => json_encode($contentData),
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'reply' => $reply
        ]);
    }

    private function injectContent(&$field, $block, $contentBlocks, $lang = 'vi', $testingCriteria = null, $properties = null)
    {
        if (isset($field['id']) && $field['id'] === 'sys_bmr_tbl_desc') {
            if (isset($field['rows']) && $field['rows'] == 6) {
                $field['rows'] = 5;
                if (isset($field['data']) && count($field['data']) >= 6) {
                    unset($field['data'][5]);
                    $field['data'] = array_values($field['data']);
                }
                if (isset($field['rowHeights']) && count($field['rowHeights']) >= 6) {
                    unset($field['rowHeights'][5]);
                    $field['rowHeights'] = array_values($field['rowHeights']);
                }
            }
        }

        if (empty($block->content)) {
            return;
        }

        if (! $contentBlocks || $contentBlocks->isEmpty()) {
            Log::warning("Designer injectContent: No content blocks found for block #{$block->id} (type: {$block->type})");
            return;
        }

        // Group content blocks by ID for easy lookup if needed
        $cbMap = $contentBlocks->keyBy('id');

        // 1. Rebuild the full HTML by replacing placeholders with text
        $fullHtml = $block->content;

        foreach ($contentBlocks as $cb) {
            $placeholder = "[[CONTENT_$cb->id]]";

            $text = '';
            if ($lang === 'en') {
                $text = $cb->en_contents ?? '';
            } elseif ($lang === 'dual') {
                $vi = $cb->vi_contents ?? '';
                $en = $cb->en_contents ?? '';
                $text = $vi.($en ? "<br><em class='text-muted small' style='display:block; border-top: 1px dashed #eee; margin-top: 4px;'>$en</em>" : '');
            } else {
                $text = $cb->vi_contents ?? '';
            }

            $fullHtml = str_replace($placeholder, $text, $fullHtml);
        }

        if ($block->type === 'static-text') {
            // Flexible regex to match any wrapper tag and extract inner content
            if (preg_match('/^<([a-z0-9]+)[^>]*>(.*)<\/\1>$/is', trim($fullHtml), $matches)) {
                $content = $matches[2];
            } else {
                $content = $fullHtml;
            }

            // --- DYNAMIC PROPERTIES REPLACEMENT ---
            if ($properties) {
                $content = $this->replacePropertySpans($content, $properties);
            }

            // --- DYNAMIC CRITERIA REPLACEMENT ---
            if ($testingCriteria) {
                $content = $this->replaceCriteriaSpans($content, $testingCriteria);
            }

            // --- VARIABLE INJECTION ---
            // Convert placeholders {{field_id}} back to spans
            $content = preg_replace_callback('/\{\{(field_[a-zA-Z0-9_]+)\}\}/', function ($m) {
                return '<span contenteditable="false" class="ebmr-field-badge" data-field-id="'.$m[1].'" onclick="selectField(event, \''.$m[1].'\')"></span>';
            }, $content);

            $field['content'] = $content;

            $contentBlock = $contentBlocks->first();
            if ($contentBlock) {
                $field['content_db_id'] = $contentBlock->id;
            }
        } elseif ($block->type === 'table') {
            $rows = $field['rows'] ?? 0;
            $cols = $field['cols'] ?? 0;

            for ($r = 0; $r < $rows; $r++) {
                if (! isset($field['data'][$r])) {
                    continue;
                }
                for ($c = 0; $c < $cols; $c++) {
                    if (! isset($field['data'][$r][$c]) || ! is_array($field['data'][$r][$c])) {
                        continue;
                    }

                    $cell = &$field['data'][$r][$c];
                    $dbId = $cell['db_id'] ?? null;

                    if ($dbId && $cbMap->has($dbId)) {
                        $cb = $cbMap->get($dbId);
                        $text = '';
                        if ($lang === 'en') {
                            $text = $cb->en_contents ?? '';
                        } elseif ($lang === 'dual') {
                            $vi = $cb->vi_contents ?? '';
                            $en = $cb->en_contents ?? '';
                            $text = $vi.($en ? "<br><em class='text-muted small' style='display:block; border-top: 1px dashed #eee; margin-top: 4px;'>$en</em>" : '');
                        } else {
                            $text = $cb->vi_contents ?? '';
                        }

                        // --- DYNAMIC PROPERTIES REPLACEMENT ---
                        if ($properties) {
                            $text = $this->replacePropertySpans($text, $properties);
                        }

                        // --- DYNAMIC CRITERIA REPLACEMENT ---
                        if ($testingCriteria) {
                            $text = $this->replaceCriteriaSpans($text, $testingCriteria);
                        }

                        // --- VARIABLE INJECTION ---
                        $text = preg_replace_callback('/\{\{(field_[a-zA-Z0-9_]+)\}\}/', function ($m) {
                            return '<span contenteditable="false" class="ebmr-field-badge" data-field-id="'.$m[1].'" onclick="selectField(event, \''.$m[1].'\')"></span>';
                        }, $text);

                        $cell['content'] = $text;
                    }
                }
            }
        }
    }

    private function replacePropertySpans($html, $properties)
    {
        if (empty($html) || !is_string($html)) {
            return $html;
        }

        return preg_replace_callback('/<span\s+([^>]*data-property-name="([^"]+)"[^>]*)>(.*?)<\/span>/is', function ($matches) use ($properties) {
            $attributes = $matches[1];
            $name = $matches[2];
            $content = $matches[3];

            if (isset($properties[$name])) {
                $propVal = $properties[$name]->value ?? '';
                return "<span {$attributes}>{$propVal}</span>";
            }
            return $matches[0];
        }, $html);
    }

    private function replaceCriteriaSpans($html, $testingCriteria)
    {
        if (empty($html) || !is_string($html)) {
            return $html;
        }

        return preg_replace_callback('/<span\s+([^>]*data-criteria-id="(\d+)"[^>]*)>(.*?)<\/span>/is', function ($matches) use ($testingCriteria) {
            $attributes = $matches[1];
            $id = $matches[2];
            $content = $matches[3];

            if (preg_match('/data-criteria-bind="(NAME|SPEC)"/i', $attributes, $bindMatches)) {
                $bind = strtoupper($bindMatches[1]);
                if (isset($testingCriteria[$id])) {
                    $criterion = $testingCriteria[$id];
                    if ($bind === 'NAME') {
                        $newContent = $criterion->name;
                        $attributes = preg_replace('/title="[^"]*"/i', 'title="Chỉ tiêu: ' . e($criterion->name) . '"', $attributes);
                    } else {
                        $newContent = $criterion->specifictions;
                        $attributes = preg_replace('/title="[^"]*"/i', 'title="Tiêu chuẩn: ' . e($criterion->name) . '"', $attributes);
                    }
                    return "<span {$attributes}>{$newContent}</span>";
                }
            }
            return $matches[0];
        }, $html);
    }

    private function overrideFieldsConfigWithTesting($fieldsConfig, $testingCriteria)
    {
        foreach ($fieldsConfig as $fieldKey => &$field) {
            if (strpos($fieldKey, 'field_crit_') === 0) {
                $testingId = substr($fieldKey, strlen('field_crit_'));
                if (isset($testingCriteria[$testingId])) {
                    $criterion = $testingCriteria[$testingId];
                    
                    // Parse limits
                    $limits = null;
                    if ($criterion->limits) {
                        $limits = is_string($criterion->limits) ? json_decode($criterion->limits, true) : (array)$criterion->limits;
                    }
                    
                    $op = $limits['operator'] ?? '=';
                    $min = $limits['value'] ?? '';
                    $max = $limits['value_high'] ?? '';
                    $unit = $limits['unit'] ?? '';
                    
                    // Determine type (numeric vs select/checkbox)
                    $isNumeric = true;
                    if ($op === 'N/A' || $op === '') {
                        if ($min === '' || !is_numeric($min)) {
                            $isNumeric = false;
                        }
                    } else if ($op === 'range' || $op === '±') {
                        if ($min === '' || !is_numeric($min) || $max === '' || !is_numeric($max)) {
                            $isNumeric = false;
                        }
                    } else {
                        if ($min === '' || !is_numeric($min)) {
                            $isNumeric = false;
                        }
                    }
                    
                    $varMin = null;
                    $varMax = null;
                    
                    if ($isNumeric) {
                        $parsedMin = ($min !== '' && is_numeric($min)) ? floatval($min) : null;
                        $parsedMax = ($max !== '' && is_numeric($max)) ? floatval($max) : null;
                        
                        if ($op === '<' || $op === '<=') {
                            $varMax = $parsedMin;
                        } else if ($op === '>' || $op === '>=') {
                            $varMin = $parsedMin;
                        } else if ($op === 'range') {
                            $varMin = $parsedMin;
                            $varMax = $parsedMax;
                        } else if ($op === '±') {
                            if ($parsedMin !== null && $parsedMax !== null) {
                                $varMin = $parsedMin - $parsedMax;
                                $varMax = $parsedMin + $parsedMax;
                            }
                        } else if ($op === '=' || $op === '') {
                            $varMin = $parsedMin;
                            $varMax = $parsedMin;
                        }
                    }
                    
                    $field['label'] = $criterion->name;
                    $field['type'] = $isNumeric ? 'number' : 'select';
                    $field['validation'] = [
                        'required' => true,
                        'min' => $varMin,
                        'max' => $varMax,
                        'decimal_places' => $field['validation']['decimal_places'] ?? null,
                    ];
                    $field['options'] = $isNumeric ? [] : ['Đạt', 'Không đạt'];
                    $field['instruction'] = 'Giới hạn tiêu chuẩn: ' . $op . ' ' . $min . ' ' . ($max ? 'đến ' . $max : '') . ' ' . $unit;
                }
            }
        }
        return $fieldsConfig;
    }

    private function generateTableHtmlStructure($field, $blockId, $templateId, $sectionId, $lang, $existingContent = null)
    {
        $rows = $field['rows'] ?? 0;
        $cols = $field['cols'] ?? 0;
        $columns = $field['columns'] ?? [];
        $hideHeader = $field['hideHeader'] ?? false;
        $touchedIds = [];

        $html = '<table class="mini-table">';

        if (! $hideHeader) {
            $html .= '<thead><tr>';
            foreach ($columns as $col) {
                $html .= '<th>'.($col['label'] ?? '').'</th>';
            }
            $html .= '</tr></thead>';
        }

        $html .= '<tbody>';
        for ($r = 0; $r < $rows; $r++) {
            $html .= '<tr>';
            for ($c = 0; $c < $cols; $c++) {
                // Save cell content and get ID
                $cellData = $field['data'][$r][$c] ?? '';
                $rawHtml = is_array($cellData) ? ($cellData['content'] ?? '') : $cellData;
                $cellDbId = is_array($cellData) ? ($cellData['db_id'] ?? null) : null;

                $preserved = null;
                if ($cellDbId && $existingContent) {
                    $preserved = $existingContent->get($cellDbId);
                }

                $contentData = [
                    'ebmr_template_blocks_id' => $blockId,
                    'template_id' => $templateId,
                    'section_id' => $sectionId,
                    'type' => 'table-cell',
                    'vi_contents' => $preserved->vi_contents ?? '',
                    'en_contents' => $preserved->en_contents ?? '',
                    'updated_at' => now(),
                ];

                if ($cellDbId && DB::table('ebmr_content_blocks')->where('id', $cellDbId)->exists()) {
                    DB::table('ebmr_content_blocks')->where('id', $cellDbId)->update($contentData);
                    $contentId = $cellDbId;
                } else {
                    $contentData['created_at'] = now();
                    $contentId = DB::table('ebmr_content_blocks')->insertGetId($contentData);
                }
                $touchedIds[] = $contentId;

                // CRITICAL: Update the ID back in the data structure
                if (is_array($field['data'][$r][$c])) {
                    $field['data'][$r][$c]['db_id'] = $contentId;
                } else {
                    $field['data'][$r][$c] = ['content' => '', 'db_id' => $contentId, 'rs' => 1, 'cs' => 1, 'hidden' => false];
                }

                $placeholder = "[[CONTENT_$contentId]]";
                [$cellStructure, $cleanText] = $this->splitHtmlAndText($rawHtml, $placeholder);

                // Update the specific language column
                $updateCol = ($lang === 'en') ? 'en_contents' : (($lang === 'vi') ? 'vi_contents' : null);
                if ($updateCol) {
                    DB::table('ebmr_content_blocks')->where('id', $contentId)->update([$updateCol => $cleanText]);
                }

                $rs = is_array($cellData) ? ($cellData['rs'] ?? 1) : 1;
                $cs = is_array($cellData) ? ($cellData['cs'] ?? 1) : 1;
                $hidden = is_array($cellData) ? ($cellData['hidden'] ?? false) : false;

                if (! $hidden) {
                    $style = '';
                    if (is_array($cellData)) {
                        if (! empty($cellData['backgroundColor'])) {
                            $style .= "background-color:{$cellData['backgroundColor']};";
                        }
                        if (! empty($cellData['textAlign'])) {
                            $style .= "text-align:{$cellData['textAlign']};";
                        }
                        if (! empty($cellData['fontWeight'])) {
                            $style .= "font-weight:{$cellData['fontWeight']};";
                        }
                        if (! empty($cellData['fontStyle'])) {
                            $style .= "font-style:{$cellData['fontStyle']};";
                        }
                        if (! empty($cellData['verticalAlign'])) {
                            $style .= "vertical-align:{$cellData['verticalAlign']};";
                        }
                    }

                    $html .= '<td'.($rs > 1 ? ' rowspan="'.$rs.'"' : '').($cs > 1 ? ' colspan="'.$cs.'"' : '').($style ? ' style="'.$style.'"' : '').'>';
                    $html .= $cellStructure;
                    $html .= '</td>';
                }
            }
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';

        return [
            'html' => $html,
            'touchedIds' => $touchedIds,
            'updatedField' => $field,
        ];
    }

    private function splitHtmlAndText($html, $placeholder)
    {
        // 1. Identify all variable spans and convert them to protected placeholders {{field_id}}
        $htmlWithPlaceholders = preg_replace_callback('/<span[^>]*class="ebmr-field-badge"[^>]*data-field-id="([^"]+)"[^>]*>.*?<\/span>/i', function ($m) {
            return '{{'.$m[1].'}}';
        }, $html);

        // We want to preserve full HTML formatting (bold, italic, list, paragraphs, breaks, alignment, etc.)
        // So we return the placeholder as the structure and the rich HTML as the text.
        return [$placeholder, $htmlWithPlaceholders];
    }

    public function aiTranslate(Request $request)
    {
        $startTimeTotal = microtime(true);
        $templateId = $request->template_id;

        Log::info("--- AI Translation Started for Template #$templateId ---");
        try {
            // Select blocks that have Vietnamese content
            $query = DB::table('ebmr_content_blocks')
                ->where('template_id', $templateId)
                ->whereNotNull('vi_contents')
                ->where('vi_contents', '!=', '')
                ->whereRaw("TRIM(REPLACE(vi_contents, CHAR(160), '')) != ''");

            // Only translate if en_contents is empty OR if user explicitly requested (we assume they did if they called this)
            // To be safe and helpful, we'll translate everything that has VI content but no EN content,
            // OR if they want to force update. For now, let's just get all VI content.
            $contents = $query->get();

            if ($contents->isEmpty()) {
                Log::info('AI Translation: No content found in ebmr_content_blocks for this template.');

                return response()->json(['success' => false, 'message' => 'Không tìm thấy nội dung Tiếng Việt nào để dịch. Hãy đảm bảo bạn đã bấm "Lưu" biểu mẫu trước khi dịch.']);
            }

            Log::info('AI Translation: Found '.$contents->count().' blocks to process.');

            $allTranslatedArray = [];
            $chunkSize = 20; // Gemini can handle more
            $chunks = $contents->chunk($chunkSize);
            $totalChunks = $chunks->count();

            foreach ($chunks as $chunkIndex => $chunk) {
                $chunkStartTime = microtime(true);
                $textsToTranslate = $chunk->pluck('vi_contents')->toArray();

                $prompt = "Translate these Vietnamese strings into English for a pharmaceutical Batch Manufacturing Record (BMR).\n".
                    "Guidelines:\n".
                    "- Use formal technical pharmaceutical terminology.\n".
                    "- Keep codes like SOP-123 or BMR-V1 unchanged.\n".
                    "- Preserve all HTML tags (e.g. <b>, <strong>, <i>, <u>, <br>, <p>, <div>, etc.) and placeholders like {{field_id}} exactly in their respective positions.\n".
                    "- Return ONLY a JSON array of strings.\n".
                    '- Input: '.json_encode($textsToTranslate, JSON_UNESCAPED_UNICODE);

                $ollamaUrl = env('OLLAMA_URL', 'http://127.0.0.1:11434');
                $modelName = env('OLLAMA_MODEL', 'qwen2.5');
                $response = \Illuminate\Support\Facades\Http::timeout(300)->withoutVerifying()
                    ->post("$ollamaUrl/api/generate", [
                        'model' => $modelName,
                        'prompt' => $prompt,
                        'format' => 'json',
                        'stream' => false,
                    ]);

                if ($response->failed()) {
                    Log::error('AI Translation: Ollama failed.');

                    return response()->json(['success' => false, 'message' => 'Lỗi kết nối tới máy chủ cục bộ Ollama (qwen2.5).']);
                }

                $resData = $response->json();
                $translatedText = $resData['response'] ?? '[]';

                // Strip markdown blocks if present
                $translatedText = preg_replace('/^```(?:json)?\s+|\s+```$/', '', trim($translatedText));

                $translatedArray = json_decode($translatedText, true);

                // If it's an object with a single array property, extract it
                if (is_array($translatedArray) && ! isset($translatedArray[0])) {
                    foreach ($translatedArray as $val) {
                        if (is_array($val) && count($val) === count($textsToTranslate)) {
                            $translatedArray = $val;
                            break;
                        }
                    }
                }

                if (! is_array($translatedArray) || count($translatedArray) === 0) {
                    Log::error('AI Translation: Invalid response format from AI. Body: '.$translatedText);

                    return response()->json(['success' => false, 'message' => 'Lỗi định dạng dữ liệu từ AI.']);
                }

                // If AI returned fewer items, pad it to avoid offset errors
                while (count($translatedArray) < count($textsToTranslate)) {
                    $translatedArray[] = '';
                }

                $allTranslatedArray = array_merge($allTranslatedArray, $translatedArray);
            }

            // Update database
            $updateCount = 0;
            foreach ($contents as $index => $cb) {
                if (isset($allTranslatedArray[$index]) && ! empty($allTranslatedArray[$index])) {
                    DB::table('ebmr_content_blocks')
                        ->where('id', $cb->id)
                        ->update([
                            'en_contents' => $allTranslatedArray[$index],
                            'updated_at' => now(),
                        ]);
                    $updateCount++;
                }
            }
            Log::info("AI Translation: Updated $updateCount records in database.");

            $durationTotal = round(microtime(true) - $startTimeTotal, 2);

            return response()->json(['success' => true, 'count' => count($allTranslatedArray), 'duration' => $durationTotal]);
        } catch (\Exception $e) {
            Log::error('AI Translation Exception: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Lỗi hệ thống: '.$e->getMessage()]);
        }
    }

    public function aiTranslateSingle(Request $request)
    {
        $startTime = microtime(true);
        try {
            $contentId = $request->content_id; // For table cells or static text
            $blockId = $request->block_id;     // Optional, if we want to translate a whole block

            $blocks = collect();
            if ($contentId) {
                $blocks = DB::table('ebmr_content_blocks')->where('id', $contentId)->get();
            } elseif ($blockId) {
                $blocks = DB::table('ebmr_content_blocks')->where('ebmr_template_blocks_id', $blockId)->get();
            }

            if ($blocks->isEmpty()) {
                return response()->json(['success' => false, 'message' => 'Không tìm thấy nội dung để dịch.']);
            }

            Log::info('AI Single Translation: Starting for '.$blocks->count().' blocks.');

            $textsToTranslate = $blocks->pluck('vi_contents')->toArray();

            // Filter out empty or whitespace-only strings
            $filteredTexts = [];
            $mapping = [];
            foreach ($textsToTranslate as $index => $text) {
                $clean = trim(str_replace(["\xc2\xa0", '&nbsp;'], ' ', $text));
                if ($clean !== '') {
                    $filteredTexts[] = $clean;
                    $mapping[] = $index;
                }
            }

            if (empty($filteredTexts)) {
                return response()->json(['success' => false, 'message' => 'Nội dung trống, không cần dịch.']);
            }

            $prompt = 'You are a professional pharmaceutical translator. 
            Translate the following list of Vietnamese strings into accurate technical English for a pharmaceutical Batch Manufacturing Record (BMR).
            Return ONLY a JSON array of strings in the EXACT same order.
            Input: '.json_encode($filteredTexts, JSON_UNESCAPED_UNICODE);

            $ollamaUrl = env('OLLAMA_URL', 'http://127.0.0.1:11434');
            $modelName = env('OLLAMA_MODEL', 'qwen2.5');
            $response = \Illuminate\Support\Facades\Http::timeout(300)->withoutVerifying()
                ->post("$ollamaUrl/api/generate", [
                    'model' => $modelName,
                    'prompt' => $prompt,
                    'format' => 'json',
                    'stream' => false,
                ]);

            if ($response->failed()) {
                return response()->json(['success' => false, 'message' => 'Lỗi kết nối Ollama AI (qwen2.5).']);
            }

            $resData = $response->json();
            $translatedText = $resData['response'] ?? null;

            // Strip markdown blocks if present
            if ($translatedText) {
                $translatedText = preg_replace('/^```(?:json)?\s+|\s+```$/', '', trim($translatedText));
            }

            $translatedArray = json_decode($translatedText, true);

            // Find array in response if wrapped in a property
            if (is_array($translatedArray) && ! isset($translatedArray[0])) {
                foreach ($translatedArray as $val) {
                    if (is_array($val) && count($val) === count($filteredTexts)) {
                        $translatedArray = $val;
                        break;
                    }
                }
            }

            if (! is_array($translatedArray) || count($translatedArray) !== count($filteredTexts)) {
                return response()->json(['success' => false, 'message' => 'Lỗi dữ liệu từ AI.']);
            }

            // Map back to original blocks
            foreach ($translatedArray as $i => $translatedValue) {
                $originalIndex = $mapping[$i];
                $block = $blocks[$originalIndex];

                DB::table('ebmr_content_blocks')
                    ->where('id', $block->id)
                    ->update([
                        'en_contents' => $translatedValue,
                        'updated_at' => now(),
                    ]);
            }

            $duration = round(microtime(true) - $startTime, 2);
            Log::info("AI Single Translation: Finished in {$duration}s.");

            return response()->json([
                'success' => true,
                'count' => count($translatedArray),
                'translations' => $translatedArray,
                'mapping' => $mapping,
                'ids' => $blocks->pluck('id')->toArray(),
            ]);
        } catch (\Exception $e) {
            Log::error('AI Single Translation Error: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function logError(Request $request)
    {
        Log::error('Client-side eBMR Error: '.$request->message, [
            'user' => session('user')['fullName'] ?? 'Guest',
            'url' => $request->url,
            'details' => $request->details,
        ]);

        return response()->json(['success' => true]);
    }

    public function getDynamicOptions(Request $request)
    {
        $table = $request->input('table');
        $labelCol = $request->input('labelCol');
        $valueCol = $request->input('valueCol');
        $whereClause = $request->input('where');

        if (! $table || ! $labelCol) {
            return response()->json(['success' => false, 'message' => 'Thiếu thông tin bảng hoặc cột hiển thị.']);
        }

        try {
            $query = DB::table($table)->select($labelCol);
            if ($valueCol) {
                $query->addSelect($valueCol);
            }

            if ($whereClause) {
                $query->whereRaw($whereClause);
            }

            $results = $query->get();
            $options = [];
            foreach ($results as $row) {
                $options[] = [
                    'label' => $row->{$labelCol},
                    'value' => $valueCol ? $row->{$valueCol} : $row->{$labelCol},
                ];
            }

            return response()->json(['success' => true, 'options' => $options]);
        } catch (\Exception $e) {
            Log::error('Dynamic Options Fetch Error: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Lỗi truy vấn: '.$e->getMessage()]);
        }
    }

    public function getEquipmentList(Request $request)
    {
        try {
            $department = $request->input('department', '');
            
            $query = DB::table('instrument')
                ->select('id', 'name', 'code', 'department_code', 'operation_SOP_code', 'clearing_SOP_code')
                ->orderBy('code', 'asc');
                
            if ($department !== '' && $department !== '-') {
                $query->where('department_code', $department);
            }
            
            $equipments = $query->get();
            
            $departments = DB::table('instrument')
                ->select('department_code')
                ->whereNotNull('department_code')
                ->where('department_code', '!=', '')
                ->distinct()
                ->orderBy('department_code', 'asc')
                ->pluck('department_code');
                
            return response()->json([
                'success' => true,
                'departments' => $departments,
                'equipments' => $equipments
            ]);
        } catch (\Exception $e) {
            Log::error('Get Equipment List Error: '.$e->getMessage());
            return response()->json(['success' => false, 'message' => 'Lỗi: '.$e->getMessage()]);
        }
    }
}
