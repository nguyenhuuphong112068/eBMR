<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class EbmrTemplateController extends Controller
{
    /**
     * List templates in drafting or submitted status
     */
    public function index(Request $request)
    {
        $this->syncStatuses();
        $type = $request->query('type', 'BMR');
        $title = 'Soạn Thảo Hồ Sơ BMR';
        if ($type === 'GF') {
            $title = 'Biểu Mẫu Dùng Chung';
        }
        if ($type === 'BPR') {
            $title = 'Hồ Sơ Đóng Gói';
        }
        if ($type === 'MF') {
            $title = 'Biểu Mẫu Gốc';
        }

        session(['title' => $title]);

        $templatesQuery = DB::table('ebmr_templates')
            // ->whereIn('ebmr_templates.status', ['draft', 'submitted'])
            ->where('ebmr_templates.type', $type)
            ->leftJoin('user_management', 'ebmr_templates.owner_id', '=', 'user_management.id')
            ->select('ebmr_templates.*', 'user_management.fullName as owner_name');

        if ($type === 'GF') {
            $templatesQuery->leftJoin('gf_category', 'ebmr_templates.caterogy_id', '=', 'gf_category.id')
                ->addSelect('gf_category.code as category_code', 'gf_category.name as category_name');
        } elseif ($type === 'MF') {
            $templatesQuery->leftJoin('mf_category', 'ebmr_templates.caterogy_id', '=', 'mf_category.id')
                ->addSelect('mf_category.code as category_code', 'mf_category.name as category_name');
        } elseif ($type === 'BPR') {
            $templatesQuery->leftJoin('finished_product_category', 'ebmr_templates.caterogy_id', '=', 'finished_product_category.id')
                ->leftJoin('product_name', 'finished_product_category.product_name_id', '=', 'product_name.id')
                ->addSelect('finished_product_category.finished_product_code as category_code', 'product_name.name as category_name');
        } else { // BMR
            $templatesQuery->leftJoin('intermediate_category', 'ebmr_templates.caterogy_id', '=', 'intermediate_category.id')
                ->leftJoin('product_name', 'intermediate_category.product_name_id', '=', 'product_name.id')
                ->addSelect('intermediate_category.intermediate_code as category_code', 'product_name.name as category_name');
        }

        $templates = $templatesQuery->orderBy('ebmr_templates.updated_at', 'desc')->get();

        $users = DB::table('user_management')->select('id', 'fullName as name')->orderBy('fullName')->get();

        // Fetch items for the selection modal based on current type
        $category_items = [];
        if ($type === 'GF') {
            $category_items = DB::table('gf_category')->where('active', 1)->get();
        } elseif ($type === 'MF') {
            $category_items = DB::table('mf_category')->where('active', 1)->get();
        } elseif ($type === 'BPR') {
            $category_items = DB::table('finished_product_category')
                ->leftJoin('product_name', 'finished_product_category.product_name_id', '=', 'product_name.id')
                ->select('finished_product_category.*', 'product_name.name as product_name')
                ->where('finished_product_category.active', 1)->get();
        } else { // BMR
            $category_items = DB::table('intermediate_category')
                ->leftJoin('product_name', 'intermediate_category.product_name_id', '=', 'product_name.id')
                ->leftJoin('dosage', 'intermediate_category.dosage_id', '=', 'dosage.id')
                ->select('intermediate_category.*', 'product_name.name as product_name', 'dosage.name as dosage_name')
                ->where('intermediate_category.active', 1)
                ->where('intermediate_category.cancel', 0)
                ->get();
        }

        $sectionsMaster = DB::table('sections')->get()->keyBy('code');

        foreach ($templates as $t) {
            // Get all unique section IDs present in this template
            $presentSectionIds = DB::table('ebmr_template_blocks')
                ->where('template_id', $t->id)
                ->whereNotNull('section_id')
                ->distinct()
                ->pluck('section_id');

            $sections = [];
            foreach ($presentSectionIds as $sid) {
                $parts = explode('_', $sid);
                $code = end($parts);

                // Try to find the section block for the label
                $sectionBlock = DB::table('ebmr_template_blocks')
                    ->where('template_id', $t->id)
                    ->where('section_id', $sid)
                    ->where('type', 'section')
                    ->first();

                $label = 'N/A';
                if ($sectionBlock) {
                    $prop = json_decode($sectionBlock->properties);
                    $label = $prop->label ?? 'N/A';
                } else {
                    // Fallback to sections master table
                    $label = $sectionsMaster[$code]->name ?? ('Phân đoạn '.$code);
                }

                $sections[] = [
                    'id' => $sid,
                    'label' => $label,
                    'code' => (int) $code, // For numerical sorting
                ];
            }

            // Sort sections by code numerically (0, 1, 2, 4, 5, 9)
            usort($sections, function ($a, $b) {
                return $a['code'] <=> $b['code'];
            });

            $t->sections = $sections;
        }

        $dosages = DB::table('dosage')->where('active', true)->get();
        $units = DB::table('unit')->where('active', true)->get();
        $materialRoles = DB::table('material_role')->orderBy('name', 'asc')->get();
        $materialSpecs = DB::table('material_spec')->orderBy('name', 'asc')->get();

        return view('pages.ebmr.templates.list', [
            'templates' => $templates,
            'users' => $users,
            'category_items' => $category_items,
            'all_sections' => $sectionsMaster->values(),
            'current_type' => $type,
            'dosages' => $dosages,
            'units' => $units,
            'materialRoles' => $materialRoles,
            'materialSpecs' => $materialSpecs,
        ]);
    }

    /**
     * Store or Update Level 1 Metadata
     */
    public function storeMetadata(Request $request)
    {
        $validated = $request->validate([
            'id' => 'nullable|integer',
            'caterogy_id' => 'required|integer',
            'version' => 'required|integer',
            'issued_date' => 'nullable|date',
            'effective_date' => 'nullable|date',
            'type' => 'nullable|string|max:10',
            'doc_code' => 'nullable|string|max:50',
        ]);

        $data = [
            'caterogy_id' => $validated['caterogy_id'],
            'version' => $validated['version'],
            'doc_code' => $validated['doc_code'] ?? null,
            'issued_date' => $request->input('issued_date'),
            'effective_date' => $request->input('effective_date'),
            'type' => $validated['type'] ?? 'BMR',
            'updated_at' => now(),
        ];

        if ($data['type'] === 'BMR') {
            $dosage_id = DB::table('intermediate_category')->where('id', $validated['caterogy_id'])->value('dosage_id');
            $dosage_name = DB::table('dosage')->where('id', $dosage_id)->value('name');
            $weight_2 = \Illuminate\Support\Str::contains(\Illuminate\Support\Str::lower($dosage_name), ['phim', 'nang']);

            $data = array_merge($data, [
                'dosage_id' => $dosage_id,
                'avg_core' => $request->input('avg_core'),
                'average_unit_weight' => $request->input('average_unit_weight'),
                'description' => $request->input('description'),
                'storage_conditions' => $request->input('storage_conditions'),
                'is_recalculation' => (int) $request->input('is_recalculation', 0),
            ]);
        }
        if (empty($validated['id'])) {
            $data['owner_id'] = session('user')['userId'] ?? null;
            $data['status'] = 'draft';
            $data['created_at'] = now();

            $id = DB::table('ebmr_templates')->insertGetId($data);

            // --- 2. Auto-generate Stages based on User Selection ---
            $selectedSections = [];
            if ($data['type'] === 'BMR') {
                $cat = DB::table('intermediate_category')->where('id', $data['caterogy_id'])->first();
                if ($cat) {
                    if (!empty($cat->weight_1)) $selectedSections[] = 1;
                    if (!empty($cat->weight_2)) $selectedSections[] = 2;
                    if (!empty($cat->prepering)) $selectedSections[] = 3;
                    if (!empty($cat->blending)) $selectedSections[] = 4;
                    if (!empty($cat->forming)) $selectedSections[] = 5;
                    if (!empty($cat->coating)) $selectedSections[] = 6;
                }
                $selectedSections[] = 9;
            } elseif ($data['type'] === 'BPR') {
                $selectedSections = [7, 8, 9];
            }

            if (! empty($selectedSections) && ($data['type'] === 'BMR' || $data['type'] === 'BPR')) {
                // Sort sections numerically (0, 1, 2... 9)
                usort($selectedSections, function ($a, $b) {
                    return (int)$a <=> (int)$b;
                });

                $sectionMeta = DB::table('sections')->whereIn('code', $selectedSections)->get()->keyBy('code');

                $order = 0;
                foreach ($selectedSections as $code) {
                    if ((int)$code === 0) continue; // Bỏ qua vì "Thông tin chung sản phẩm" đã được tạo tự động bằng virtual blocks

                    $sName = $sectionMeta[$code]->name ?? ('Phân đoạn '.$code);
                    $sectionIdStr = $data['caterogy_id'].'_'.$code;
                    
                    DB::table('ebmr_template_blocks')->insert([
                        'template_id' => $id,
                        'section_id' => $sectionIdStr,
                        'type' => 'section',
                        'label' => 'section_'.$order,
                        'order' => $order++,
                        'properties' => json_encode([
                            'id' => 'blk_sec_'.uniqid(),
                            'type' => 'section',
                            'label' => $sName,
                            'stage_code' => $code,
                            'section_id' => $sectionIdStr
                        ]),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            $message = 'Khởi tạo hồ sơ mới thành công';
        } else {
            DB::table('ebmr_templates')->where('id', $validated['id'])->update($data);
            $id = $validated['id'];
            $message = 'Cập nhật thông tin hồ sơ thành công';
        }

        if ($data['type'] === 'BMR' && $request->has('bom') && is_array($request->bom)) {
            // Clear existing formulas for this template if updating
            $existingFormulaIds = DB::table('preparation_formula')->where('ebmr_templates_id', $id)->pluck('id');
            if ($existingFormulaIds->isNotEmpty()) {
                DB::table('formula_materials')->whereIn('preparation_formula_id', $existingFormulaIds)->delete();
                DB::table('ingredient_amount')->whereIn('preparation_formula_id', $existingFormulaIds)->delete();
                DB::table('preparation_formula')->where('ebmr_templates_id', $id)->delete();
            }

            foreach ($request->bom as $bomItem) {
                $materials = !empty($bomItem['materials']) && is_array($bomItem['materials']) ? $bomItem['materials'] : [];
                // Fallback for old structure or single material
                if (empty($materials) && (!empty($bomItem['code']) || !empty($bomItem['name']))) {
                    $materials[] = [
                        'code' => $bomItem['code'] ?? null,
                        'name' => $bomItem['name'] ?? null,
                        'manufacturer' => $bomItem['manufacturer'] ?? null,
                        'Spec' => $bomItem['Spec'] ?? null,
                    ];
                }

                if (!empty($materials)) {
                    $firstMat = $materials[0] ?? [];
                    if (!empty($firstMat['code']) || !empty($firstMat['name'])) {
                        $formulaId = DB::table('preparation_formula')->insertGetId([
                            'ebmr_templates_id' => $id,
                            'type' => $bomItem['type'] ?? 0,
                            'role' => $bomItem['role'] ?? null,
                            'total_amount_per_unit' => $bomItem['total_amount_per_unit'] ?: null,
                            'total_amount_per_batch' => $bomItem['total_amount_per_batch'] ?: null,
                            'created_by' => session('user')['fullName'] ?? null,
                            'created_at' => now(),
                        ]);

                        foreach ($materials as $mat) {
                            if (!empty($mat['code']) || !empty($mat['name'])) {
                                DB::table('formula_materials')->insert([
                                    'preparation_formula_id' => $formulaId,
                                    'code' => $mat['code'] ?? null,
                                    'name' => $mat['name'] ?? null,
                                    'manufacturer' => $mat['manufacturer'] ?? null,
                                    'Spec' => $mat['Spec'] ?? null,
                                    'created_at' => now(),
                                ]);
                            }
                        }

                        if (isset($bomItem['sub_amounts']) && is_array($bomItem['sub_amounts'])) {
                            foreach ($bomItem['sub_amounts'] as $sub) {
                                if (!empty($sub['amount_per_unit'])) {
                                    DB::table('ingredient_amount')->insert([
                                        'preparation_formula_id' => $formulaId,
                                        'amount_per_unit' => $sub['amount_per_unit'],
                                        'amount_per_batch' => $sub['amount_per_batch'] ?? null,
                                        'note' => $sub['note'] ?? null,
                                        'created_by' => session('user')['fullName'] ?? null,
                                        'created_at' => now(),
                                    ]);
                                }
                            }
                        }
                    }
                }
            }
        }

        $templateObj = DB::table('ebmr_templates')->where('id', $id)->first();
        if ($templateObj && $templateObj->type === 'BMR') {
            $this->ensureRecalculationBlocks($templateObj);
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'id' => $id,
        ]);
    }

    /**
     * Update only effective date
     */
    public function updateEffectiveDate(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer',
            'effective_date' => 'required|date',
        ]);

        $template = DB::table('ebmr_templates')->where('id', $validated['id'])->first();
        if (!$template) {
            return response()->json(['success' => false, 'message' => 'Hồ sơ không tồn tại']);
        }

        if ($template->owner_id != (session('user')['userId'] ?? null)) {
            return response()->json(['success' => false, 'message' => 'Bạn không có quyền thực hiện thao tác này']);
        }

        $effectiveDate = Carbon::parse($validated['effective_date']);
        $newStatus = $effectiveDate->isFuture() ? 'issued' : 'active';

        DB::table('ebmr_templates')->where('id', $validated['id'])->update([
            'status' => $newStatus,
            'effective_date' => $validated['effective_date'],
            'updated_at' => now(),
        ]);

        if ($newStatus === 'active') {
            $this->expirePreviousVersions($validated['id']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật ngày hiệu lực thành công',
        ]);
    }

    /**
     * Sync statuses: Activate reached dates and expire old versions
     */
    private function syncStatuses()
    {
        // 1. Activate templates where effective_date <= now
        $toActivate = DB::table('ebmr_templates')
            ->where('status', 'issued')
            ->whereNotNull('effective_date')
            ->where('effective_date', '<=', now()->toDateString())
            ->get();

        foreach ($toActivate as $t) {
            DB::table('ebmr_templates')->where('id', $t->id)->update([
                'status' => 'active',
                'updated_at' => now()
            ]);
            $this->expirePreviousVersions($t->id);
        }
    }

    /**
     * Expire previous versions of the same category and type
     */
    private function expirePreviousVersions($templateId)
    {
        $current = DB::table('ebmr_templates')->where('id', $templateId)->first();
        if (!$current) return;

        DB::table('ebmr_templates')
            ->where('caterogy_id', $current->caterogy_id)
            ->where('type', $current->type)
            ->where('version', '<', $current->version)
            ->where('status', 'active')
            ->update([
                'status' => 'expired',
                'updated_at' => now()
            ]);
    }

    public function getNextVersion(Request $request)
    {
        $categoryId = $request->category_id;
        $type = $request->type ?? 'BMR';

        $maxVersion = DB::table('ebmr_templates')
            ->where('caterogy_id', $categoryId)
            ->where('type', $type)
            ->max('version');

        return response()->json([
            'next_version' => ($maxVersion ?? 0) + 1,
        ]);
    }

    public function getMetadata($id)
    {
        $template = DB::table('ebmr_templates')->where('id', $id)->first();

        if ($template) {
            if ($template->type === 'BMR') {
                $cat = DB::table('intermediate_category')->where('id', $template->caterogy_id)->first();
                $template->batch_qty = $cat->batch_qty ?? 0;
                $template->batch_size = $cat->batch_size ?? 0;
            }

            $formulas = DB::table('preparation_formula')
                ->where('ebmr_templates_id', $id)
                ->orderBy('id')
                ->get();

            foreach ($formulas as $formula) {
                $formula->materials = DB::table('formula_materials')
                    ->where('preparation_formula_id', $formula->id)
                    ->orderBy('id')
                    ->get();
                    
                $formula->sub_amounts = DB::table('ingredient_amount')
                    ->where('preparation_formula_id', $formula->id)
                    ->orderBy('id')
                    ->get();
            }
            $template->bom = $formulas;
        }

        return response()->json($template);
    }

    public function getTemplates()
    {
        $templates = DB::table('ebmr_templates')
            ->select('ebmr_templates.id', 'ebmr_templates.updated_at', 'ebmr_templates.log_history', 'ebmr_templates.type', 'ebmr_templates.caterogy_id')
            ->orderBy('ebmr_templates.updated_at', 'desc')
            ->get();

        foreach ($templates as $t) {
            if ($t->type === 'GF') {
                $t->name = DB::table('gf_category')->where('id', $t->caterogy_id)->value('name') ?? 'N/A';
            } elseif ($t->type === 'MF') {
                $t->name = DB::table('mf_category')->where('id', $t->caterogy_id)->value('name') ?? 'N/A';
            } elseif ($t->type === 'BPR') {
                $t->name = DB::table('finished_product_category')
                    ->leftJoin('product_name', 'finished_product_category.product_name_id', '=', 'product_name.id')
                    ->where('finished_product_category.id', $t->caterogy_id)
                    ->value('product_name.name') ?? 'N/A';
            } else {
                $t->name = DB::table('intermediate_category')
                    ->leftJoin('product_name', 'intermediate_category.product_name_id', '=', 'product_name.id')
                    ->where('intermediate_category.id', $t->caterogy_id)
                    ->value('product_name.name') ?? 'N/A';
            }
        }

        return response()->json($templates);
    }

    public function getHistory($id)
    {
        $history = DB::table('ebmr_revision_history')
            ->where('template_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($history);
    }

    public function getTemplateBlocks($id)
    {
        $blocks = DB::table('ebmr_template_blocks')
            ->where('template_id', $id)
            ->orderBy('order')
            ->get();

        $blockIds = $blocks->pluck('id')->toArray();
        $contentBlocks = DB::table('ebmr_content_blocks')
            ->whereIn('ebmr_template_blocks_id', $blockIds)
            ->get()
            ->groupBy('ebmr_template_blocks_id');

        $resultBlocks = $blocks->map(function ($b) use ($contentBlocks) {
            $prop = json_decode($b->properties, true);
            $this->injectContent($prop, $b, $contentBlocks->get($b->id));
            $prop['db_type'] = $b->type;

            return (object) $prop;
        });

        // Also fetch fields configuration for this template
        $variants = DB::table('ebmr_variants')->where('template_id', $id)->get();
        $fieldsConfig = [];
        foreach ($variants as $v) {
            $config = json_decode($v->config, true) ?? [];
            $fieldsConfig[$v->field_key] = array_merge([
                'id' => $v->field_key,
                'name' => $v->name,
                'label' => $v->label,
                'type' => $v->type,
                'section_id' => $v->section_id,
                'block_id' => $v->block_id,
            ], $config);
        }

        return response()->json([
            'blocks' => $resultBlocks,
            'fields' => $fieldsConfig,
        ]);
    }

    private function injectContent(&$field, $block, $contentBlocks)
    {
        if (! $contentBlocks || empty($block->content)) {
            return;
        }

        // 1. Rebuild the full HTML by replacing placeholders with text
        $fullHtml = $block->content;
        foreach ($contentBlocks as $cb) {
            $placeholder = "[[CONTENT_$cb->id]]";
            $text = $cb->vi_contents ?? '';
            $fullHtml = str_replace($placeholder, $text, $fullHtml);
        }

        if ($block->type === 'static-text') {
            if (preg_match('/<div class="static-text-display"[^>]*>(.*?)<\/div>/is', $fullHtml, $matches)) {
                $content = $matches[1];
            } else {
                $content = $fullHtml;
            }

            // --- VARIABLE INJECTION ---
            $content = preg_replace_callback('/\{\{(field_[a-zA-Z0-9_]+)\}\}/', function ($m) {
                return '<span contenteditable="false" class="ebmr-field-badge" data-field-id="'.$m[1].'" onclick="selectField(event, \''.$m[1].'\')"></span>';
            }, $content);

            $field['content'] = $content;
        } elseif ($block->type === 'table') {
            $rows = $field['rows'] ?? 0;
            $cols = $field['cols'] ?? 0;
            $cbMap = $contentBlocks ? $contentBlocks->keyBy('id') : collect();

            if (! isset($field['data'])) {
                $field['data'] = [];
            }
            for ($r = 0; $r < $rows; $r++) {
                if (! isset($field['data'][$r])) {
                    $field['data'][$r] = [];
                }
                for ($c = 0; $c < $cols; $c++) {
                    if (isset($field['data'][$r][$c]) && is_array($field['data'][$r][$c])) {
                        $cell = &$field['data'][$r][$c];
                        $dbId = $cell['db_id'] ?? null;
                        if ($dbId && $cbMap->has($dbId)) {
                            $cb = $cbMap->get($dbId);
                            $content = $cb->vi_contents ?? '';
                            
                            // --- VARIABLE INJECTION ---
                            $content = preg_replace_callback('/\{\{(field_[a-zA-Z0-9_]+)\}\}/', function ($m) {
                                return '<span contenteditable="false" class="ebmr-field-badge" data-field-id="'.$m[1].'" onclick="selectField(event, \''.$m[1].'\')"></span>';
                            }, $content);
                            
                            $cell['content'] = $content;
                        }
                    } else {
                        $field['data'][$r][$c] = ['content' => '', 'rs' => 1, 'cs' => 1, 'hidden' => false];
                    }
                }
            }
        }
    }

    public function getMaterialInfo(Request $request)
    {
        $code = trim($request->input('code'));
        if (!$code) {
            return response()->json(['success' => false, 'message' => 'Missing code']);
        }

        try {
            // Find material
            $material = DB::connection('mms')
                ->table('mstmaterial')
                ->where('MatID', $code)
                ->first();

            $matName = $material ? $material->MatNM : '';

            // Find supplier
            $supplierName = '';
            $supMat = DB::connection('mms')
                ->table('MSTSUPMAT')
                ->where('MatID', $code)
                ->first();

            if ($supMat && isset($supMat->mfgid)) {
                $sup = DB::connection('mms')
                    ->table('MSTSUP')
                    ->where('SupID', $supMat->mfgid)
                    ->first();
                if ($sup) {
                    $supplierName = $sup->SupNM;
                }
            }

            return response()->json([
                'success' => true,
                'name' => $matName,
                'manufacturer' => $supplierName
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    protected function ensureRecalculationBlocks($template)
    {
        $id = $template->id;
        $recalcSectionId = $template->caterogy_id . '_recalc';
        
        if ($template->is_recalculation == 1) {
            $hasRecalc = DB::table('ebmr_template_blocks')
                ->where('template_id', $id)
                ->where('section_id', $recalcSectionId)
                ->exists();

            if (!$hasRecalc) {
                // Shift all existing blocks order by 2 to make space at the beginning (order 0 and 1)
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
        } else {
            // Delete recalc blocks if unchecked
            $recalcBlocks = DB::table('ebmr_template_blocks')
                ->where('template_id', $id)
                ->where('section_id', $recalcSectionId)
                ->get();
            if ($recalcBlocks->isNotEmpty()) {
                $recalcBlockIds = $recalcBlocks->pluck('id')->toArray();
                DB::table('ebmr_template_blocks')->whereIn('id', $recalcBlockIds)->delete();
                DB::table('ebmr_content_blocks')->whereIn('ebmr_template_blocks_id', $recalcBlockIds)->delete();
            }
        }
    }

    /**
     * Get BMR template sections (stages) and saved testing criteria
     */
    public function getTestingData($id)
    {
        // Get all unique section IDs present in this template
        $presentSectionIds = DB::table('ebmr_template_blocks')
            ->where('template_id', $id)
            ->whereNotNull('section_id')
            ->distinct()
            ->pluck('section_id');

        $sectionsMaster = DB::table('sections')->get()->keyBy('code');

        $excludeStages = [
            'TÍNH TOÁN CÔNG THỨC', 
            'CÂN NGUYÊN LIỆU', 
            'PHIẾU KIỂM NGHIỆM', 
            'PHIẾU KN',
            'PHIẾU KIỂM NGHIỆM BÁN THÀNH PHẨM',
            'TÍNH TOÁN CÔNG THỨC VÀ CÂN'
        ];

        $sections = [];
        foreach ($presentSectionIds as $sid) {
            $parts = explode('_', $sid);
            $code = end($parts);

            // Try to find the section block for the label
            $sectionBlock = DB::table('ebmr_template_blocks')
                ->where('template_id', $id)
                ->where('section_id', $sid)
                ->where('type', 'section')
                ->first();

            $label = 'N/A';
            if ($sectionBlock) {
                $prop = json_decode($sectionBlock->properties);
                $label = $prop->label ?? 'N/A';
            } else {
                // Fallback to sections master table
                $label = $sectionsMaster[$code]->name ?? ('Phân đoạn '.$code);
            }

            // Exclude non-standard stages
            if (in_array(mb_strtoupper(trim($label)), $excludeStages)) {
                continue;
            }

            $sections[] = [
                'id' => $sid,
                'label' => $label,
                'code' => (int) $code, // For numerical sorting
            ];
        }

        // Sort sections by code numerically
        usort($sections, function ($a, $b) {
            return $a['code'] <=> $b['code'];
        });

        // Query any existing testing criteria for this template
        $testing = DB::table('testing')
            ->where('ebmr_templace_id', $id)
            ->orderBy('stt', 'asc')
            ->get()
            ->map(function ($row) {
                // Check if specifictions is a JSON string
                $decoded = json_decode($row->specifictions);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $row->specifictions = $decoded;
                }
                
                $row->limits = json_decode($row->limits);

                // Fetch associated images
                $row->images = DB::table('testing_images')
                    ->where('testing_id', $row->id)
                    ->orderBy('id', 'asc')
                    ->get()
                    ->toArray();

                return $row;
            });

        return response()->json([
            'success' => true,
            'sections' => $sections,
            'testing' => $testing,
        ]);
    }

    /**
     * Save BMR template testing criteria
     */
    public function saveTestingData(Request $request, $id)
    {
        $criteria = $request->input('criteria', []);

        DB::beginTransaction();
        try {
            // Delete previous testing criteria and images for this template
            $oldTestingIds = DB::table('testing')
                ->where('ebmr_templace_id', $id)
                ->pluck('id');
            
            DB::table('testing_images')->whereIn('testing_id', $oldTestingIds)->delete();
            DB::table('testing')->where('ebmr_templace_id', $id)->delete();

            // Insert new testing criteria
            foreach ($criteria as $item) {
                $specs = $item['specifictions'] ?? '';
                if (is_array($specs)) {
                    $specs = json_encode($specs, JSON_UNESCAPED_UNICODE);
                }

                $limitsJson = isset($item['limits']) ? json_encode($item['limits'], JSON_UNESCAPED_UNICODE) : json_encode(null);

                $testingId = DB::table('testing')->insertGetId([
                    'ebmr_templace_id' => $id,
                    'stage' => $item['stage'] ?? '',
                    'stt' => (int)($item['stt'] ?? 1),
                    'name' => $item['name'] ?? '',
                    'specifictions' => $specs,
                    'limits' => $limitsJson,
                    'note' => $item['note'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Save associated images and rename them according to ebmr_templates_id + testing_id + index
                $images = $item['images'] ?? [];
                foreach ($images as $idx => $img) {
                    $originalPath = $img['image_path'] ?? '';
                    $finalPath = $originalPath;

                    if ($originalPath) {
                        $localPath = public_path(ltrim($originalPath, '/'));
                        if (file_exists($localPath)) {
                            $extension = pathinfo($localPath, PATHINFO_EXTENSION);
                            $newName = "{$id}_{$testingId}_{$idx}.{$extension}";
                            $newLocalPath = public_path("img/testing_img/{$newName}");

                            // Ensure directory exists
                            $dir = dirname($newLocalPath);
                            if (!file_exists($dir)) {
                                mkdir($dir, 0755, true);
                            }

                            if ($localPath !== $newLocalPath) {
                                rename($localPath, $newLocalPath);
                            }
                            $finalPath = "/img/testing_img/{$newName}";
                        }
                    }

                    DB::table('testing_images')->insert([
                        'testing_id' => $testingId,
                        'image_path' => $finalPath,
                        'image_name' => $img['image_name'] ?? '',
                        'image_description' => $img['image_description'] ?? '',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Lưu tiêu chuẩn kiểm nghiệm thành công!',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Đã xảy ra lỗi khi lưu tiêu chuẩn: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upload an image for testing criteria
     */
    public function uploadTestingImage(Request $request)
    {
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $originalName = $file->getClientOriginalName();
            $filename = 'temp_' . time() . '_' . \Illuminate\Support\Str::random(8) . '.' . $file->getClientOriginalExtension();
            
            // Ensure directory exists
            $dir = public_path('img/testing_img');
            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
            }
            
            $file->move($dir, $filename);
            
            return response()->json([
                'success' => true,
                'url' => '/img/testing_img/' . $filename,
                'name' => pathinfo($originalName, PATHINFO_FILENAME)
            ]);
        }
        return response()->json(['success' => false, 'message' => 'Không tìm thấy file hình ảnh'], 400);
    }
}

