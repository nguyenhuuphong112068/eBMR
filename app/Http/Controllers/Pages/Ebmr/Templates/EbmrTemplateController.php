<?php

namespace App\Http\Controllers\Pages\Ebmr\Templates;

use App\Http\Controllers\Controller;

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
        \App\Services\DocumentActivationService::activateAllIssuedDocuments();
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
        if ($type === 'CO') {
            $title = 'Danh Sách Thành Phần';
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
        } elseif ($type === 'CO') {
            $templatesQuery->leftJoin('co_category', 'ebmr_templates.caterogy_id', '=', 'co_category.id')
                ->addSelect('co_category.code as category_code', 'co_category.name as category_name');
        } else { // BMR
            $templatesQuery->leftJoin('intermediate_category', 'ebmr_templates.caterogy_id', '=', 'intermediate_category.id')
                ->leftJoin('product_name', 'intermediate_category.product_name_id', '=', 'product_name.id')
                ->addSelect('intermediate_category.intermediate_code as category_code', 'product_name.name as category_name');
        }

        $templates = $templatesQuery->get();

        // Find all categories that have a pending (draft/submitted/issued) template of the current type
        $pendingCategoryIds = DB::table('ebmr_templates')
            ->where('type', $type)
            ->whereIn('status', ['draft', 'submitted', 'issued'])
            ->whereNotNull('caterogy_id')
            ->distinct()
            ->pluck('caterogy_id')
            ->toArray();

        $maxVersions = [];
        foreach ($templates as $t) {
            $catId = $t->caterogy_id;
            $ver = (int)$t->version;
            if (!isset($maxVersions[$catId]) || $ver > $maxVersions[$catId]) {
                $maxVersions[$catId] = $ver;
            }
        }

        // Fetch all active ingredients (formulas of role 'Hoạt Chất') for these templates
        $templateIds = $templates->pluck('id')->toArray();
        $activeIngredients = [];
        if (!empty($templateIds)) {
            $activeIngredients = DB::table('formula_preparation')
                ->join('formula_materials', 'formula_preparation.id', '=', 'formula_materials.preparation_formula_id')
                ->whereIn('formula_preparation.ebmr_templates_id', $templateIds)
                ->whereRaw('LOWER(TRIM(formula_preparation.role)) = ?', ['hoạt chất'])
                ->select(
                    'formula_preparation.ebmr_templates_id',
                    'formula_preparation.total_amount_per_unit',
                    'formula_materials.name as material_name'
                )
                ->get()
                ->groupBy('ebmr_templates_id');
        }

        foreach ($templates as $t) {
            $t->is_max_version = ($t->caterogy_id && (int)$t->version === ($maxVersions[$t->caterogy_id] ?? 0));
            $t->has_pending_version = in_array($t->caterogy_id, $pendingCategoryIds);
            
            // Build Labeled Strength (Hàm lượng nhãn)
            $strengthParts = [];
            if (isset($activeIngredients[$t->id])) {
                foreach ($activeIngredients[$t->id] as $ing) {
                    $matName = $ing->material_name;
                    if (strpos($matName, '(') !== false) {
                        $matName = trim(explode('(', $matName)[0]);
                    }
                    $amount = floatval($ing->total_amount_per_unit);
                    $strengthParts[] = "{$matName} {$amount} mg";
                }
            }
            $t->labeled_strength = !empty($strengthParts) ? implode(' / ', $strengthParts) : '';

            $t->current_workflow_step = null;
            if ($t->status === 'submitted') {
                $pendingWf = DB::table('ebmr_template_workflows')
                    ->where('template_id', $t->id)
                    ->where('status', 'pending')
                    ->orderBy('step_order', 'asc')
                    ->first();
                if ($pendingWf) {
                    $user = DB::table('user_management')->where('id', $pendingWf->user_id)->first();
                    $roleStr = $pendingWf->role === 'reviewer' ? 'Kiểm tra' : ($pendingWf->role === 'approver' ? 'Phê duyệt' : 'Ban hành');
                    $t->current_workflow_step = 'Đang chờ ' . $roleStr . ' (' . ($user->fullName ?? 'Unknown') . ')';
                }
            }
        }

        $users = DB::table('user_management')->select('id', 'fullName as name')->orderBy('fullName')->get();

        // Fetch items for the selection modal based on current type
        $category_items = [];
        if ($type === 'GF') {
            $category_items = DB::table('gf_category')->where('active', 1)->get();
        } elseif ($type === 'CO') {
            $category_items = DB::table('co_category')->where('active', 1)->get();
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

        // Sort templates: is_max_version DESC, updated_at DESC
        $templates = $templates->sortByDesc(function($t) {
            return [
                $t->is_max_version ? 1 : 0,
                $t->updated_at
            ];
        })->values();

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
            } elseif (in_array($data['type'], ['MF', 'GF'])) {
                $sectionIdStr = $data['caterogy_id'] . '_' . $data['type'];
                DB::table('ebmr_template_blocks')->insert([
                    'template_id' => $id,
                    'section_id' => $sectionIdStr,
                    'type' => 'section',
                    'label' => 'section_0',
                    'order' => 0,
                    'properties' => json_encode([
                        'id' => 'blk_sec_'.uniqid(),
                        'type' => 'section',
                        'label' => 'Nội Dung',
                        'stage_code' => $data['type'],
                        'section_id' => $sectionIdStr
                    ]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $message = 'Khởi tạo hồ sơ mới thành công';
        } else {
            DB::table('ebmr_templates')->where('id', $validated['id'])->update($data);
            $id = $validated['id'];
            $message = 'Cập nhật thông tin hồ sơ thành công';
        }

        if ($data['type'] === 'BMR') {
            $submittedBOM = $request->input('bom', []);
            $submittedIds = collect($submittedBOM)->pluck('id')->filter()->toArray();

            // Lấy tất cả công thức hiện tại của template này đang có active=1
            $existingFormulaIds = DB::table('formula_preparation')
                ->where('ebmr_templates_id', $id)
                ->where('active', 1)
                ->pluck('id')
                ->toArray();

            // Những ID có trong DB nhưng không có trong form submit => Bị xóa mềm
            $idsToSoftDelete = array_diff($existingFormulaIds, $submittedIds);
            if (!empty($idsToSoftDelete)) {
                DB::table('formula_preparation')
                    ->whereIn('id', $idsToSoftDelete)
                    ->update(['active' => 0]);
            }

            foreach ($submittedBOM as $bomItem) {
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
                        $formulaId = $bomItem['id'] ?? null;
                        $batchVal = isset($bomItem['total_amount_per_batch']) ? str_replace(['(', ')'], '', $bomItem['total_amount_per_batch']) : null;

                        $formulaData = [
                            'ebmr_templates_id' => $id,
                            'type' => $bomItem['type'] ?? 0,
                            'role' => $bomItem['role'] ?? null,
                            'total_amount_per_unit' => !empty($bomItem['total_amount_per_unit']) ? $bomItem['total_amount_per_unit'] : null,
                            'total_amount_per_batch' => $batchVal ?: null,
                            'number_of_lots' => $bomItem['number_of_lots'] ?? 1,
                            'amounts_of_lots' => !empty($bomItem['amounts_of_lots']) ? $bomItem['amounts_of_lots'] : null,
                            'not_calculator' => isset($bomItem['not_calculator']) ? 1 : 0,
                        ];

                        $bomCellNotes = isset($bomItem['cell_notes']) && is_array($bomItem['cell_notes']) 
                            ? array_filter($bomItem['cell_notes'], fn($v) => trim((string)$v) !== '') 
                            : [];
                        $formulaData['cell_notes'] = !empty($bomCellNotes) ? json_encode($bomCellNotes, JSON_UNESCAPED_UNICODE) : null;

                        if ($formulaId && in_array($formulaId, $existingFormulaIds)) {
                            // Cập nhật dòng có sẵn
                            DB::table('formula_preparation')->where('id', $formulaId)->update(array_merge($formulaData, [
                                'updated_at' => now()
                            ]));
                            
                            // Cập nhật lại materials và sub_amounts: Hard delete và re-insert (do không cần quan tâm ID của 2 bảng con này)
                            DB::table('formula_materials')->where('preparation_formula_id', $formulaId)->delete();
                            DB::table('formula_ingredient_amount')->where('preparation_formula_id', $formulaId)->delete();
                        } else {
                            // Thêm dòng mới
                            $formulaData['created_by'] = session('user')['fullName'] ?? null;
                            $formulaData['created_at'] = now();
                            $formulaData['active'] = 1;
                            $formulaId = DB::table('formula_preparation')->insertGetId($formulaData);
                        }

                        foreach ($materials as $mat) {
                            if (!empty($mat['code']) || !empty($mat['name'])) {
                                $matCellNotes = isset($mat['cell_notes']) && is_array($mat['cell_notes']) 
                                    ? array_filter($mat['cell_notes'], fn($v) => trim((string)$v) !== '') 
                                    : [];

                                DB::table('formula_materials')->insert([
                                    'preparation_formula_id' => $formulaId,
                                    'code' => $mat['code'] ?? null,
                                    'name' => $mat['name'] ?? null,
                                    'manufacturer' => $mat['manufacturer'] ?? null,
                                    'Spec' => $mat['Spec'] ?? null,
                                    'cell_notes' => !empty($matCellNotes) ? json_encode($matCellNotes, JSON_UNESCAPED_UNICODE) : null,
                                    'created_at' => now(),
                                ]);
                            }
                        }

                        if (isset($bomItem['sub_amounts']) && is_array($bomItem['sub_amounts'])) {
                            foreach ($bomItem['sub_amounts'] as $sub) {
                                if (!empty($sub['amount_per_unit'])) {
                                    DB::table('formula_ingredient_amount')->insert([
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
    public function storeCoCategory(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:255|unique:co_category,code',
            'name' => 'required|string|max:255',
        ]);

        $avatarPath = null;
        if ($request->hasFile('avatar')) {
            $file = $request->file('avatar');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('upLoadData/img/co_avatars'), $filename);
            $avatarPath = 'upLoadData/img/co_avatars/' . $filename;
        }

        $caterogyId = DB::table('co_category')->insertGetId([
            'code' => $validated['code'],
            'name' => $validated['name'],
            'is_private' => $request->has('is_private') ? 1 : 0,
            'avatar' => $avatarPath,
            'active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Auto create template
        $templateId = DB::table('ebmr_templates')->insertGetId([
            'caterogy_id' => $caterogyId,
            'version' => 1,
            'type' => 'CO',
            'status' => 'draft',
            'owner_id' => session('user')['userId'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Auto create section block for CO
        $sectionIdStr = $caterogyId . '_CO';
        DB::table('ebmr_template_blocks')->insert([
            'template_id' => $templateId,
            'section_id' => $sectionIdStr,
            'type' => 'section',
            'label' => 'section_0',
            'order' => 0,
            'properties' => json_encode([
                'id' => 'blk_sec_'.uniqid(),
                'type' => 'section',
                'label' => 'Nội Dung',
                'stage_code' => 'CO',
                'section_id' => $sectionIdStr
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tạo danh mục thành phần thành công',
            'id' => $caterogyId,
            'template_id' => $templateId
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
            \App\Services\DocumentActivationService::expirePreviousEbmrVersions($validated['id']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật ngày hiệu lực thành công',
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

        $nextVersion = ($maxVersion ?? 0) + 1;
        if ($type === 'MF' && is_null($maxVersion)) {
            $nextVersion = 0;
        }

        return response()->json([
            'next_version' => $nextVersion,
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
                $template->product_name = DB::table('product_name')->where('id', $cat->product_name_id ?? 0)->value('name') ?? '';
                $template->product_code = $cat->intermediate_code ?? '';
                $template->unit_batch_size = $cat->unit_batch_size ?? '';
                $template->dosage_form_name = DB::table('dosage')->where('id', $template->dosage_id)->value('name') ?? '-';
            }

            $formulas = DB::table('formula_preparation')
                ->where('ebmr_templates_id', $id)
                ->where('active', 1)
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
            $template->bom = $formulas;
        }

        return response()->json($template);
    }

    public function getTemplates()
    {
        $templates = DB::table('ebmr_templates')
            ->select('ebmr_templates.id', 'ebmr_templates.updated_at', 'ebmr_templates.log_history', 'ebmr_templates.type', 'ebmr_templates.caterogy_id', 'ebmr_templates.status')
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
            } elseif ($t->type === 'CO') {
                $t->name = DB::table('co_category')->where('id', $t->caterogy_id)->value('name') ?? 'N/A';
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
            ->leftJoin('user_management', 'ebmr_revision_history.user_id', '=', 'user_management.id')
            ->where('ebmr_revision_history.template_id', $id)
            ->select('ebmr_revision_history.*', 'user_management.fullName as user_name')
            ->orderBy('ebmr_revision_history.created_at', 'desc')
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

        // Fetch testing criteria and properties for dynamic reference replacement
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

        $resultBlocks = $blocks->map(function ($b) use ($contentBlocks, $testingCriteria, $properties) {
            $prop = json_decode($b->properties, true);
            $this->injectContent($prop, $b, $contentBlocks->get($b->id), $testingCriteria, $properties);
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

        // Dynamically override fieldsConfig from testing criteria
        $fieldsConfig = $this->overrideFieldsConfigWithTesting($fieldsConfig, $testingCriteria);

        // Fetch template info to allow client to generate virtual header
        $baseTemplate = DB::table('ebmr_templates')->where('id', $id)->first();
        $query = DB::table('ebmr_templates')->where('ebmr_templates.id', $id);
        if ($baseTemplate && $baseTemplate->type === 'CO') {
            $query->leftJoin('co_category', 'ebmr_templates.caterogy_id', '=', 'co_category.id')
                  ->select('ebmr_templates.*', 'co_category.code as category_code', 'co_category.name as category_name');
        } else {
            $query->leftJoin('gf_category', 'ebmr_templates.caterogy_id', '=', 'gf_category.id')
                  ->select('ebmr_templates.*', 'gf_category.code as category_code', 'gf_category.name as category_name');
        }
        $templateInfo = $query->first();

        return response()->json([
            'blocks' => $resultBlocks,
            'fields' => $fieldsConfig,
            'template' => $templateInfo,
        ]);
    }

    private function injectContent(&$field, $block, $contentBlocks, $testingCriteria = null, $properties = null)
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

            // --- DYNAMIC PROPERTIES REPLACEMENT ---
            if ($properties) {
                $content = $this->replacePropertySpans($content, $properties);
            }

            // --- DYNAMIC CRITERIA REPLACEMENT ---
            if ($testingCriteria) {
                $content = $this->replaceCriteriaSpans($content, $testingCriteria);
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

                            // --- DYNAMIC PROPERTIES REPLACEMENT ---
                            if ($properties) {
                                $content = $this->replacePropertySpans($content, $properties);
                            }

                            // --- DYNAMIC CRITERIA REPLACEMENT ---
                            if ($testingCriteria) {
                                $content = $this->replaceCriteriaSpans($content, $testingCriteria);
                            }
                            
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
        $template = DB::table('ebmr_templates')->where('id', $id)->first();
        if (!$template) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy hồ sơ mẫu.'
            ], 404);
        }
        if ($template->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Hồ sơ mẫu không còn ở trạng thái nháp, không thể cấu hình.'
            ], 403);
        }

        $criteria = $request->input('criteria', []);

        DB::beginTransaction();
        try {
            // Track active/valid testing IDs to keep
            $keepTestingIds = [];

            // Process each criterion
            foreach ($criteria as $item) {
                $specs = $item['specifictions'] ?? '';
                if (is_array($specs)) {
                    $specs = json_encode($specs, JSON_UNESCAPED_UNICODE);
                }

                $limitsJson = isset($item['limits']) ? json_encode($item['limits'], JSON_UNESCAPED_UNICODE) : json_encode(null);

                $criteriaId = $item['id'] ?? null;
                $testingData = [
                    'ebmr_templace_id' => $id,
                    'stage' => $item['stage'] ?? '',
                    'stt' => (int)($item['stt'] ?? 1),
                    'name' => $item['name'] ?? '',
                    'specifictions' => $specs,
                    'limits' => $limitsJson,
                    'note' => $item['note'] ?? null,
                    'updated_at' => now(),
                ];

                if ($criteriaId && DB::table('testing')->where('id', $criteriaId)->where('ebmr_templace_id', $id)->exists()) {
                    DB::table('testing')->where('id', $criteriaId)->update($testingData);
                    $testingId = $criteriaId;
                } else {
                    $testingData['created_at'] = now();
                    $testingId = DB::table('testing')->insertGetId($testingData);
                }

                $keepTestingIds[] = $testingId;

                // For images, clear and re-insert for this testing ID
                DB::table('testing_images')->where('testing_id', $testingId)->delete();

                $images = $item['images'] ?? [];
                foreach ($images as $idx => $img) {
                    $originalPath = $img['image_path'] ?? '';
                    $finalPath = $originalPath;

                    if ($originalPath) {
                        $localPath = public_path(ltrim($originalPath, '/'));
                        if (file_exists($localPath)) {
                            $extension = pathinfo($localPath, PATHINFO_EXTENSION);
                            $newName = "{$id}_{$testingId}_{$idx}.{$extension}";
                            $newLocalPath = public_path("upLoadData/img/testing/{$newName}");

                            // Ensure directory exists
                            $dir = dirname($newLocalPath);
                            if (!file_exists($dir)) {
                                mkdir($dir, 0755, true);
                            }

                            if ($localPath !== $newLocalPath) {
                                rename($localPath, $newLocalPath);
                            }
                            $finalPath = "/upLoadData/img/testing/{$newName}";
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

            // Delete any testing criteria (and their images) that were removed from this template
            $removedTestingQuery = DB::table('testing')
                ->where('ebmr_templace_id', $id)
                ->whereNotIn('id', $keepTestingIds);
            
            $removedTestingIds = $removedTestingQuery->pluck('id');
            DB::table('testing_images')->whereIn('testing_id', $removedTestingIds)->delete();
            $removedTestingQuery->delete();

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
            $dir = public_path('upLoadData/img/testing');
            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
            }
            
            $file->move($dir, $filename);
            
            return response()->json([
                'success' => true,
                'url' => '/upLoadData/img/testing/' . $filename,
                'name' => pathinfo($originalName, PATHINFO_FILENAME)
            ]);
        }
        return response()->json(['success' => false, 'message' => 'Không tìm thấy file hình ảnh'], 400);
    }

    /**
     * Get BMR template properties
     */
    public function getProperties($id)
    {
        $properties = DB::table('ebmr_properties')
            ->where('ebmr_templace_id', $id)
            ->orderBy('name', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'properties' => $properties
        ]);
    }

    /**
     * Save/update property
     */
    public function saveProperty(Request $request, $id)
    {
        $name = trim($request->input('name'));
        $value = $request->input('value');
        $propId = $request->input('prop_id');
        
        $userSession = session('user');
        // Let's also check Auth or other session helpers if user session isn't populated
        $userName = 'System';
        if (is_array($userSession)) {
            $userName = $userSession['fullName'] ?? $userSession['username'] ?? $userSession['user_name'] ?? 'System';
        } else if (is_object($userSession)) {
            $userName = $userSession->fullName ?? $userSession->username ?? $userSession->user_name ?? 'System';
        } else if (auth()->check()) {
            $userName = auth()->user()->fullName ?? auth()->user()->userName ?? auth()->user()->name ?? 'System';
        }

        if (empty($name)) {
            return response()->json(['success' => false, 'message' => 'Tên thuộc tính không được để trống'], 400);
        }

        try {
            if (!empty($propId)) {
                DB::table('ebmr_properties')
                    ->where('ebmr_templace_id', $id)
                    ->where('id', $propId)
                    ->update([
                        'name' => $name,
                        'value' => $value,
                        'created_by' => $userName,
                        'updated_at' => now()
                    ]);
            } else {
                DB::table('ebmr_properties')->updateOrInsert(
                    ['ebmr_templace_id' => $id, 'name' => $name],
                    [
                        'value' => $value,
                        'created_by' => $userName,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]
                );
            }

            $property = DB::table('ebmr_properties')
                ->where('ebmr_templace_id', $id)
                ->where('name', $name)
                ->first();

            return response()->json([
                'success' => true,
                'message' => 'Lưu thuộc tính thành công',
                'property' => $property
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lưu thuộc tính: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete property
     */
    public function deleteProperty($id, $propId)
    {
        try {
            DB::table('ebmr_properties')
                ->where('ebmr_templace_id', $id)
                ->where('id', $propId)
                ->delete();

            return response()->json([
                'success' => true,
                'message' => 'Xóa thuộc tính thành công'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi xóa thuộc tính: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get rooms configuration for BMR template
     */
    public function getRoomsConfig($id)
    {
        try {
            $template = DB::table('ebmr_templates')->where('id', $id)->first();
            if (!$template) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy hồ sơ mẫu.'
                ], 404);
            }

            // Get present sections
            $presentSectionIds = DB::table('ebmr_template_blocks')
                ->where('template_id', $id)
                ->whereNotNull('section_id')
                ->distinct()
                ->pluck('section_id');

            $sectionsMaster = DB::table('sections')->get()->keyBy('code');
            $sections = [];
            foreach ($presentSectionIds as $sid) {
                $parts = explode('_', $sid);
                $code = end($parts);

                // Only allow manufacturing stages 1 to 7
                if (!is_numeric($code) || (int) $code < 1 || (int) $code > 7) {
                    continue;
                }

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
                    $label = $sectionsMaster[$code]->name ?? ('Phân đoạn '.$code);
                }

                $sections[] = [
                    'id' => $sid,
                    'label' => $label,
                    'code' => (int) $code,
                ];
            }

            usort($sections, function ($a, $b) {
                return $a['code'] <=> $b['code'];
            });

            // Get all rooms from pms connection
            $rooms = DB::connection('pms')->table('room')
                ->where('active', 1)
                ->orderBy('code')
                ->get();

            // Get all conditions from local ebr database
            $conditions = DB::table('room_manufactured_condition')
                ->orderBy('name')
                ->get();

            // Get currently assigned rooms
            $assignments = DB::table('ebmr_template_rooms')
                ->where('template_id', $id)
                ->get();

            return response()->json([
                'success' => true,
                'template' => $template,
                'sections' => $sections,
                'rooms' => $rooms,
                'conditions' => $conditions,
                'assignments' => $assignments
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi tải cấu hình phòng: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Save rooms configuration for BMR template
     */
    public function saveRoomsConfig(Request $request, $id)
    {
        try {
            $template = DB::table('ebmr_templates')->where('id', $id)->first();
            if (!$template) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy hồ sơ mẫu.'
                ], 404);
            }
            if ($template->status !== 'draft') {
                return response()->json([
                    'success' => false,
                    'message' => 'Hồ sơ mẫu không còn ở trạng thái nháp, không thể cấu hình.'
                ], 403);
            }

            $mappings = $request->input('mappings', []);

            DB::transaction(function () use ($id, $mappings) {
                // Delete old mappings
                DB::table('ebmr_template_rooms')->where('template_id', $id)->delete();

                // Insert new mappings
                $toInsert = [];
                foreach ($mappings as $map) {
                    if (empty($map['section_id']) || empty($map['room_id'])) {
                        continue;
                    }
                    $toInsert[] = [
                        'template_id' => $id,
                        'section_id' => $map['section_id'],
                        'room_id' => $map['room_id'],
                        'condition_id' => !empty($map['condition_id']) ? $map['condition_id'] : null,
                        'created_at' => now(),
                        'updated_at' => now()
                    ];
                }

                if (!empty($toInsert)) {
                    DB::table('ebmr_template_rooms')->insert($toInsert);
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'Lưu khai báo phòng sản xuất thành công!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lưu cấu hình phòng: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Duplicate an existing template to create a new draft edition/version.
     */
    public function duplicateTemplate(Request $request)
    {
        $oldId = $request->input('id');
        if (!$oldId) {
            return response()->json(['success' => false, 'message' => 'Thiếu ID hồ sơ mẫu.']);
        }

        $oldTemplate = DB::table('ebmr_templates')->where('id', $oldId)->first();
        if (!$oldTemplate) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy hồ sơ mẫu gốc.']);
        }

        // Check if there is already a pending template of the same category and type
        $pendingExists = DB::table('ebmr_templates')
            ->where('caterogy_id', $oldTemplate->caterogy_id)
            ->where('type', $oldTemplate->type)
            ->whereIn('status', ['draft', 'submitted', 'issued'])
            ->exists();
        if ($pendingExists) {
            return response()->json([
                'success' => false,
                'message' => 'Đã tồn tại một bản sao đang soạn thảo hoặc phê duyệt (Draft/Submitted/Issued) cho danh mục này. Không thể tạo thêm bản sao mới.'
            ]);
        }

        // 1. Calculate next version
        $maxVersion = DB::table('ebmr_templates')
            ->where('caterogy_id', $oldTemplate->caterogy_id)
            ->where('type', $oldTemplate->type)
            ->max('version');
        $nextVersion = ($maxVersion ?? 0) + 1;

        $userId = session('user')['userId'] ?? null;

        DB::beginTransaction();
        try {
            // 2. Clone ebmr_templates metadata
            $newTemplateId = DB::table('ebmr_templates')->insertGetId([
                'type' => $oldTemplate->type,
                'doc_code' => $oldTemplate->doc_code,
                'caterogy_id' => $oldTemplate->caterogy_id,
                'owner_id' => $userId,
                'status' => 'draft',
                'log_history' => $oldTemplate->log_history,
                'version' => $nextVersion,
                'pre_version' => $oldTemplate->version,
                'dosage_id' => $oldTemplate->dosage_id,
                'avg_core' => $oldTemplate->avg_core,
                'average_unit_weight' => $oldTemplate->average_unit_weight,
                'description' => $oldTemplate->description,
                'storage_conditions' => $oldTemplate->storage_conditions,
                'is_recalculation' => $oldTemplate->is_recalculation,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // 3. Clone testing criteria (testing table) and keep track of ID mapping
            $testingMapping = [];
            $testings = DB::table('testing')->where('ebmr_templace_id', $oldId)->get();
            foreach ($testings as $test) {
                $newTestId = DB::table('testing')->insertGetId([
                    'ebmr_templace_id' => $newTemplateId,
                    'stage' => $test->stage,
                    'stt' => $test->stt,
                    'name' => $test->name,
                    'specifictions' => $test->specifictions,
                    'limits' => $test->limits,
                    'note' => $test->note,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $testingMapping[$test->id] = $newTestId;

                // Clone testing_images for this criteria
                $images = DB::table('testing_images')->where('testing_id', $test->id)->get();
                foreach ($images as $img) {
                    DB::table('testing_images')->insert([
                        'testing_id' => $newTestId,
                        'image_path' => $img->image_path,
                        'image_name' => $img->image_name,
                        'image_description' => $img->image_description,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            // 4. Clone ebmr_properties
            $properties = DB::table('ebmr_properties')->where('ebmr_templace_id', $oldId)->get();
            foreach ($properties as $prop) {
                DB::table('ebmr_properties')->insert([
                    'ebmr_templace_id' => $newTemplateId,
                    'name' => $prop->name,
                    'value' => $prop->value,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // 5. Clone ebmr_template_blocks and map their IDs
            $blockMapping = [];
            $blocks = DB::table('ebmr_template_blocks')->where('template_id', $oldId)->orderBy('order')->get();
            foreach ($blocks as $block) {
                // Determine new block properties JSON
                $propsArr = json_decode($block->properties, true);
                if (is_array($propsArr)) {
                    // Update any testing criteria references in properties if they exist
                    if (isset($propsArr['criteria_id']) && isset($testingMapping[$propsArr['criteria_id']])) {
                        $propsArr['criteria_id'] = $testingMapping[$propsArr['criteria_id']];
                    }
                }
                
                $propertiesJson = json_encode($propsArr, JSON_UNESCAPED_UNICODE);
                foreach ($testingMapping as $oldTestId => $newTestId) {
                    $propertiesJson = str_replace('data-criteria-id="' . $oldTestId . '"', 'data-criteria-id="' . $newTestId . '"', $propertiesJson);
                    $propertiesJson = str_replace('"' . $oldTestId . '"', '"' . $newTestId . '"', $propertiesJson);
                }

                $contentStr = $block->content;
                if ($contentStr) {
                    foreach ($testingMapping as $oldTestId => $newTestId) {
                        $contentStr = str_replace('data-criteria-id="' . $oldTestId . '"', 'data-criteria-id="' . $newTestId . '"', $contentStr);
                    }
                }

                $newBlockId = DB::table('ebmr_template_blocks')->insertGetId([
                    'template_id' => $newTemplateId,
                    'section_id' => $block->section_id,
                    'type' => $block->type,
                    'label' => $block->label,
                    'order' => $block->order,
                    'content' => $contentStr,
                    'properties' => $propertiesJson,
                    'fields_config' => $block->fields_config,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $blockMapping[$block->id] = $newBlockId;
            }

            // 6. Clone ebmr_content_blocks for each template block
            $contentBlocks = DB::table('ebmr_content_blocks')->where('template_id', $oldId)->get();
            foreach ($contentBlocks as $cb) {
                $viContents = $cb->vi_contents;
                $enContents = $cb->en_contents;
                if ($viContents) {
                    foreach ($testingMapping as $oldTestId => $newTestId) {
                        $viContents = str_replace('data-criteria-id="' . $oldTestId . '"', 'data-criteria-id="' . $newTestId . '"', $viContents);
                    }
                }
                if ($enContents) {
                    foreach ($testingMapping as $oldTestId => $newTestId) {
                        $enContents = str_replace('data-criteria-id="' . $oldTestId . '"', 'data-criteria-id="' . $newTestId . '"', $enContents);
                    }
                }

                $newCbBlockId = $blockMapping[$cb->ebmr_template_blocks_id] ?? null;
                if ($newCbBlockId) {
                    DB::table('ebmr_content_blocks')->insert([
                        'ebmr_template_blocks_id' => $newCbBlockId,
                        'template_id' => $newTemplateId,
                        'section_id' => $cb->section_id,
                        'type' => $cb->type,
                        'vi_contents' => $viContents,
                        'en_contents' => $enContents,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            // 7. Clone ebmr_variants and map block IDs
            $variants = DB::table('ebmr_variants')->where('template_id', $oldId)->get();
            foreach ($variants as $var) {
                $newVarBlockId = $var->block_id ? ($blockMapping[$var->block_id] ?? null) : null;
                
                DB::table('ebmr_variants')->insert([
                    'template_id' => $newTemplateId,
                    'field_key' => $var->field_key,
                    'name' => $var->name,
                    'label' => $var->label,
                    'type' => $var->type,
                    'important_var_id' => $var->important_var_id,
                    'section_id' => $var->section_id,
                    'block_id' => $newVarBlockId,
                    'config' => $var->config,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // 8. Clone formulas and recipe data
            $formulas = DB::table('formula_preparation')->where('ebmr_templates_id', $oldId)->get();
            foreach ($formulas as $formula) {
                $newFormulaId = DB::table('formula_preparation')->insertGetId([
                    'ebmr_templates_id' => $newTemplateId,
                    'type' => $formula->type,
                    'role' => $formula->role,
                    'total_amount_per_unit' => $formula->total_amount_per_unit,
                    'total_amount_per_batch' => $formula->total_amount_per_batch,
                    'created_by' => $formula->created_by,
                    'created_at' => now(),
                ]);

                // Clone formula_materials
                $materials = DB::table('formula_materials')->where('preparation_formula_id', $formula->id)->get();
                foreach ($materials as $mat) {
                    DB::table('formula_materials')->insert([
                        'preparation_formula_id' => $newFormulaId,
                        'code' => $mat->code,
                        'name' => $mat->name,
                        'manufacturer' => $mat->manufacturer,
                        'Spec' => $mat->Spec,
                        'created_at' => now(),
                    ]);
                }

                // Clone ingredient_amount
                $subAmounts = DB::table('formula_ingredient_amount')->where('preparation_formula_id', $formula->id)->get();
                foreach ($subAmounts as $sub) {
                    DB::table('formula_ingredient_amount')->insert([
                        'preparation_formula_id' => $newFormulaId,
                        'amount_per_unit' => $sub->amount_per_unit,
                        'amount_per_batch' => $sub->amount_per_batch,
                        'note' => $sub->note,
                        'created_by' => $sub->created_by,
                        'created_at' => now(),
                    ]);
                }
            }

            // 9. Clone ebmr_template_rooms
            $rooms = DB::table('ebmr_template_rooms')->where('template_id', $oldId)->get();
            foreach ($rooms as $room) {
                DB::table('ebmr_template_rooms')->insert([
                    'template_id' => $newTemplateId,
                    'section_id' => $room->section_id,
                    'room_id' => $room->room_id,
                    'condition_id' => $room->condition_id,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Lên ấn bản (phiên bản ' . $nextVersion . ') thành công.',
                'new_id' => $newTemplateId,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Lỗi nhân bản dữ liệu: ' . $e->getMessage()
            ], 500);
        }
    }
}

