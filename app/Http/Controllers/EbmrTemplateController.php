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

        return view('pages.ebmr.templates.list', [
            'templates' => $templates,
            'users' => $users,
            'category_items' => $category_items,
            'all_sections' => $sectionsMaster->values(),
            'current_type' => $type,
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
        ]);

        $data = [
            'caterogy_id' => $validated['caterogy_id'],
            'version' => $validated['version'],
            'issued_date' => $request->input('issued_date'),
            'effective_date' => $request->input('effective_date'),
            'type' => $validated['type'] ?? 'BMR',
            'updated_at' => now(),
        ];
        if (empty($validated['id'])) {
            $data['owner_id'] = session('user')['userId'] ?? null;
            $data['status'] = 'draft';
            $data['created_at'] = now();

            $id = DB::table('ebmr_templates')->insertGetId($data);

            // --- 1. ALWAYS Create the Persistent Header Section (category_id) ---
            $headerOrder = 0;
            DB::table('ebmr_template_blocks')->insert([
                'template_id' => $id,
                'section_id' => $data['caterogy_id'],
                'type' => 'section',
                'label' => 'BMR HEADER',
                'order' => $headerOrder++,
                'properties' => json_encode([
                    'id' => 'blk_sec_sys_header_' . time(),
                    'type' => 'section',
                    'label' => 'BMR HEADER',
                    'locked' => true,
                    'section_id' => $data['caterogy_id']
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('ebmr_template_blocks')->insert([
                'template_id' => $id,
                'section_id' => $data['caterogy_id'],
                'type' => 'table',
                'label' => 'blk_header_'.time(),
                'order' => $headerOrder++,
                'properties' => json_encode([
                    'id' => 'blk_header_'.time(),
                    'type' => 'table',
                    'label' => ($data['type'] === 'GF' ? 'GF Header' : 'BMR Header'),
                    'is'.($data['type'] === 'GF' ? 'GfHeader' : 'BmrHeader') => true,
                    'locked' => true,
                    'section_id' => $data['caterogy_id']
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // --- 2. Auto-generate Stages based on User Selection ---
            $selectedSections = $request->input('selected_sections', []);
            if (! empty($selectedSections) && ($data['type'] === 'BMR' || $data['type'] === 'BPR')) {
                // Sort sections numerically (0, 1, 2... 9)
                usort($selectedSections, function ($a, $b) {
                    return (int)$a <=> (int)$b;
                });

                $sectionMeta = DB::table('sections')->whereIn('code', $selectedSections)->get()->keyBy('code');

                $order = $headerOrder;
                foreach ($selectedSections as $code) {
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
            $content = preg_replace_callback('/\{\{(field_[0-9]+)\}\}/', function ($m) {
                return '<span contenteditable="false" class="ebmr-field-badge" data-field-id="'.$m[1].'" onclick="selectField(event, \''.$m[1].'\')"></span>';
            }, $content);

            $field['content'] = $content;
        } elseif ($block->type === 'table') {
            $rows = $field['rows'] ?? 0;
            $cols = $field['cols'] ?? 0;
            $data = [];

            preg_match_all('/<td[^>]*>(.*?)<\/td>/is', $fullHtml, $matches);
            $tdContents = $matches[1] ?? [];

            $idx = 0;
            if (! isset($field['data'])) {
                $field['data'] = [];
            }
            for ($r = 0; $r < $rows; $r++) {
                if (! isset($field['data'][$r])) {
                    $field['data'][$r] = [];
                }
                for ($c = 0; $c < $cols; $c++) {
                    $content = $tdContents[$idx] ?? '';
                    
                    // --- VARIABLE INJECTION ---
                    $content = preg_replace_callback('/\{\{(field_[0-9]+)\}\}/', function ($m) {
                        return '<span contenteditable="false" class="ebmr-field-badge" data-field-id="'.$m[1].'" onclick="selectField(event, \''.$m[1].'\')"></span>';
                    }, $content);

                    if (isset($field['data'][$r][$c]) && is_array($field['data'][$r][$c])) {
                        $field['data'][$r][$c]['content'] = $content;
                    } else {
                        $field['data'][$r][$c] = ['content' => $content, 'rs' => 1, 'cs' => 1, 'hidden' => false];
                    }
                    $idx++;
                }
            }
        }
    }
}
