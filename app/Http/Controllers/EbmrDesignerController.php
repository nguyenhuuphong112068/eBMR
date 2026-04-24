<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
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
                $template = DB::table('ebmr_templates')->where('id', $id)->first();
                if ($template) {
                    // Update session title based on type
                    $type = $template->type ?? 'BMR';
                    $title = 'Thiết kế hồ sơ BMR';
                    if ($type === 'GF') $title = 'Thiết kế biểu mẫu dùng chung';
                    if ($type === 'BPR') $title = 'Thiết kế hồ sơ đóng gói';
                    if ($type === 'MF') $title = 'Thiết kế biểu mẫu gốc';
                    session(['title' => $title]);

                    // Get category extra info for header display
                    if ($type === 'GF') {
                        $cat = DB::table('gf_category')->where('id', $template->caterogy_id)->first();
                        $template->category_code = $cat->code ?? '';
                        $template->category_name = $cat->name ?? '';
                        $template->relatived_sop_no = $cat->relatived_sop_no ?? '';
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
                        $template->batch_size = ($cat->batch_size ?? '') . ' ' . ($cat->unit_batch_size ?? '');
                    }
                    // Determine read-only state
                    $currentUserId = session('user')['userId'] ?? null;
                    if ($template->owner_id != $currentUserId || $template->status !== 'draft') {
                        $isReadOnly = true;
                    }

                    $blocks = DB::table('ebmr_template_blocks')->where('template_id', $id)->orderBy('order')->get();

                    // Fetch all content blocks for this template's blocks to minimize queries
                    $blockIds = $blocks->pluck('id')->toArray();
                    $contentBlocks = DB::table('ebmr_content_blocks')
                        ->whereIn('ebmr_template_blocks_id', $blockIds)
                        ->get()
                        ->groupBy('ebmr_template_blocks_id');

                    $fields = [];
                    $fieldsConfig = new \stdClass();
                    if ($blocks->isNotEmpty()) {
                        $fieldsConfig = json_decode($blocks->first()->fields_config);

                        $allFields = [];
                        $categoryId = $template->caterogy_id ?? 0;
                        $currentSectionId = ($template->type === 'BMR' || $template->type === 'BPR') ? ($categoryId . '_0') : null;

                        foreach ($blocks as $block) {
                            $f = json_decode($block->properties, true); // Decode as array to modify easily

                            // Inject content back from ebmr_content_blocks
                            $this->injectContent($f, $block, $contentBlocks->get($block->id), $lang);

                            // Ensure block has a unique ID for frontend tracking
                            if (!isset($f['id'])) {
                                $f['id'] = 'blk_' . $block->id . '_' . uniqid();
                                // Update DB to persist this ID
                                DB::table('ebmr_template_blocks')->where('id', $block->id)->update(['properties' => json_encode($f)]);
                            }

                            $f['db_id'] = $block->id;

                            // Auto-repair section_id if it's missing but we can determine it
                            if (isset($f['type']) && $f['type'] === 'section' && !empty($f['stage_code'])) {
                                $currentSectionId = $categoryId . '_' . $f['stage_code'];
                            }

                            // If DB has NULL but we have a current section, fix it in memory for the response
                            if (empty($block->section_id) && $currentSectionId) {
                                $block->section_id = $currentSectionId;
                                DB::table('ebmr_template_blocks')->where('id', $block->id)->update(['section_id' => $currentSectionId]);
                            }

                            // If we are filtering by section, only add if it matches
                            if (!$sectionId || $block->section_id == $sectionId) {
                                $f['section_id'] = $block->section_id; // Ensure section_id is in property for sorting
                                $f['block_order'] = $block->order;
                                $fields[] = (object)$f;
                            }
                        }

                        // Sort fields globally: first by section code, then by original block order
                        usort($fields, function ($a, $b) {
                            $partsA = explode('_', $a->section_id ?? '');
                            $codeA = (int)end($partsA);

                            $partsB = explode('_', $b->section_id ?? '');
                            $codeB = (int)end($partsB);

                            if ($codeA !== $codeB) {
                                return $codeA <=> $codeB;
                            }
                            return $a->block_order <=> $b->block_order;
                        });
                    }
                    $template->schema = (object)['fields' => $fields, 'fieldsConfig' => $fieldsConfig];

                    // Load comments
                    $comments = DB::table('ebmr_template_comments')
                        ->leftJoin('user_management', 'ebmr_template_comments.user_id', '=', 'user_management.id')
                        ->where('template_id', $id)
                        ->select('ebmr_template_comments.*', 'user_management.fullName as user_name')
                        ->orderBy('created_at', 'asc')
                        ->get();

                    return view('pages.ebmr.designer', [
                        'template' => $template,
                        'isReadOnly' => ($lang === 'dual') ? true : $isReadOnly,
                        'comments' => $comments,
                        'activeSectionId' => $sectionId,
                        'lang' => $lang
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Designer error: ' . $e->getMessage());
        }
        return redirect()->back();
    }

    public function save(Request $request)
    {
        try {
            $validated = $request->validate([
                'id' => 'nullable|integer',
                'schema' => 'required|array',
                'log_history' => 'nullable|boolean',
                'lang' => 'nullable|string'
            ]);

            $lang = $validated['lang'] ?? 'vi';
            $schemaData = $validated['schema'];
            $fields = $schemaData['fields'] ?? [];
            $fieldsConfig = $schemaData['fieldsConfig'] ?? null;
            $sectionId = $request->section_id;

            $data = [
                'updated_at' => now()
            ];

            if (isset($validated['log_history'])) {
                $data['log_history'] = $validated['log_history'];
            }

            if (!empty($validated['id'])) {
                $oldTemplateId = $validated['id'];
                $oldTemplateRecord = DB::table('ebmr_templates')->where('id', $oldTemplateId)->first();

                if ($oldTemplateRecord) {
                    $oldTemplate = clone($oldTemplateRecord);

                    // Reconstruct old schema for logging
                    $oldBlocks = DB::table('ebmr_template_blocks')->where('template_id', $oldTemplateId)->get();
                    $oldFields = [];
                    foreach ($oldBlocks as $ob) {
                        $oldFields[] = json_decode($ob->properties, true);
                    }
                    $oldTemplate->schema = json_encode(['fields' => $oldFields]);

                    if (!empty($oldTemplate->log_history)) {
                        $this->logRevision($oldTemplate, $schemaData);
                    }

                    // Preserve existing translations in memory for lookup
                    $existingContent = DB::table('ebmr_content_blocks')
                        ->where('template_id', $oldTemplateId)
                        ->get()
                        ->keyBy('id');

                    DB::table('ebmr_templates')->where('id', $oldTemplateId)->update($data);
                    $id = $oldTemplateId;

                    // Keep track of which blocks we touch so we can delete the orphans later
                    $touchedBlockIds = [];
                    $touchedContentIds = [];
                } else {
                    return response()->json(['success' => false, 'message' => 'Không tìm thấy hồ sơ gốc'], 404);
                }
            } else {
                $existingContent = collect();
                $data['created_at'] = now();
                $id = DB::table('ebmr_templates')->insertGetId($data);
                $touchedBlockIds = [];
                $touchedContentIds = [];
            }

            // Insert/Update blocks
            $templateRecord = DB::table('ebmr_templates')->where('id', $id)->first();
            $categoryId = $templateRecord->caterogy_id ?? 0;
            $currentSectionId = ($templateRecord->type === 'BMR' || $templateRecord->type === 'BPR') ? ($categoryId . '_0') : null;

            $order = 0;
            foreach ($fields as $field) {
                $type = $field['type'] ?? 'unknown';

                if ($type === 'section') {
                    $stageCode = $field['stage_code'] ?? 'USER_DEFINED_' . $order;
                    $currentSectionId = $categoryId . '_' . $stageCode;
                }

                $finalSectionId = $sectionId ?: $currentSectionId;
                $properties = $field;

                // Process table data separately
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
                    'label' => $field['id'] ?? null,
                    'order' => $order++,
                    'properties' => json_encode($properties),
                    'fields_config' => json_encode($fieldsConfig),
                    'updated_at' => now()
                ];

                if ($blockDbId && DB::table('ebmr_template_blocks')->where('id', $blockDbId)->exists()) {
                    DB::table('ebmr_template_blocks')->where('id', $blockDbId)->update($blockData);
                    $blockId = $blockDbId;
                } else {
                    $blockData['created_at'] = now();
                    $blockId = DB::table('ebmr_template_blocks')->insertGetId($blockData);
                }
                $touchedBlockIds[] = $blockId;

                // 2. Save content blocks and build HTML structure
                $htmlStructure = '';
                if ($type === 'static-text') {
                    $rawHtml = $field['content'] ?? '';
                    $preserved = null;
                    $contentDbId = $field['content_db_id'] ?? null;
                    
                    if ($contentDbId) {
                        $preserved = $existingContent->get($contentDbId);
                    }

                    $contentData = [
                        'ebmr_template_blocks_id' => $blockId,
                        'template_id' => $id,
                        'section_id' => $finalSectionId,
                        'type' => $type,
                        'vi_contents' => $preserved->vi_contents ?? '',
                        'en_contents' => $preserved->en_contents ?? '',
                        'updated_at' => now()
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
                    $htmlStructure = '<div class="static-text-display">' . $structure . '</div>';

                } elseif ($type === 'table') {
                    $tableResult = $this->generateTableHtmlStructure($field, $blockId, $id, $finalSectionId, $lang, $existingContent);
                    $htmlStructure = $tableResult['html'];
                    $touchedContentIds = array_merge($touchedContentIds, $tableResult['touchedIds']);
                    
                    // CRITICAL: Update properties with the IDs we just generated/matched
                    $properties = $tableResult['updatedField'];
                    DB::table('ebmr_template_blocks')->where('id', $blockId)->update(['properties' => json_encode($properties)]);
                } elseif ($type === 'signature') {
                    $htmlStructure = '<div class="block-signature-placeholder text-muted py-5 text-center border rounded bg-light">
                                        <i class="fas fa-pen-nib me-2"></i>' . ($field['label'] ?? 'Ký xác nhận') . '
                                      </div>';
                } elseif ($type === 'linked-template') {
                    $htmlStructure = '<div class="block-linked-placeholder py-4 px-3 border border-primary border-dashed rounded bg-light text-center">
                                        <i class="fas fa-link me-2 text-primary"></i> <strong>Biểu mẫu chung: ' . ($field['label'] ?? 'Chưa đặt tên') . '</strong>
                                      </div>';
                }

                DB::table('ebmr_template_blocks')->where('id', $blockId)->update(['content' => $htmlStructure]);
            }

            // DELETE ORPHANS (Blocks and content that are no longer in this section/template)
            if ($id) {
                $deleteQueryBlocks = DB::table('ebmr_template_blocks')->where('template_id', $id);
                $deleteQueryContent = DB::table('ebmr_content_blocks')->where('template_id', $id);

                if ($sectionId) {
                    $deleteQueryBlocks->where('section_id', $sectionId);
                    $deleteQueryContent->where('section_id', $sectionId);
                }

                $deleteQueryBlocks->whereNotIn('id', $touchedBlockIds)->delete();
                $deleteQueryContent->whereNotIn('id', $touchedContentIds)->delete();
            }

            return response()->json(['success' => true, 'message' => 'Lưu hồ sơ thành công', 'id' => $id]);
        } catch (\Exception $e) {
            Log::error('EbmrDesignerController@save error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Lỗi hệ thống: ' . $e->getMessage()], 500);
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
        foreach ($oldFields as $f) $oldMap[$f['id']] = $f;
        $newMap = [];
        foreach ($newFields as $f) $newMap[$f['id']] = $f;

        foreach ($newFields as $nf) {
            if (!isset($oldMap[$nf['id']])) {
                $added[] = $nf['label'] ?: $nf['type'];
            } else {
                $of = $oldMap[$nf['id']];
                $isDiff = (($of['label'] ?? null) !== ($nf['label'] ?? null)) ||
                    (($of['content'] ?? null) !== ($nf['content'] ?? null)) ||
                    (($of['type'] ?? null) !== ($nf['type'] ?? null)) ||
                    (($of['rows'] ?? 0) !== ($nf['rows'] ?? 0)) ||
                    (($of['cols'] ?? 0) !== ($nf['cols'] ?? 0));

                if ($isDiff) $modified[] = $nf['label'] ?: $nf['type'];
            }
        }
        foreach ($oldFields as $of) {
            if (!isset($newMap[$of['id']])) $deleted[] = $of['label'] ?: $of['type'];
        }

        if (empty($added) && empty($deleted) && empty($modified)) return;

        $summaryParts = [];
        if (!empty($added)) $summaryParts[] = "Thêm: " . implode(', ', $added);
        if (!empty($deleted)) $summaryParts[] = "Xóa: " . implode(', ', $deleted);
        if (!empty($modified)) $summaryParts[] = "Điều chỉnh: " . implode(', ', $modified);

        DB::table('ebmr_revision_history')->insert([
            'template_id' => $oldTemplate->id,
            'user_id' => session('user')['userId'] ?? null,
            'change_summary' => implode(' | ', $summaryParts),
            'details' => json_encode(['added' => $added, 'deleted' => $deleted, 'modified' => $modified]),
            'created_at' => now()
        ]);
    }

    public function storeComment(Request $request)
    {
        $validated = $request->validate([
            'template_id' => 'required|integer',
            'content' => 'required|string',
            'selection_id' => 'nullable|string',
            'selection_data' => 'nullable|array'
        ]);

        $id = DB::table('ebmr_template_comments')->insertGetId([
            'template_id' => $validated['template_id'],
            'user_id' => session('user')['userId'],
            'content' => $validated['content'],
            'selection_id' => $validated['selection_id'],
            'selection_data' => isset($validated['selection_data']) ? json_encode($validated['selection_data']) : null,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $user = DB::table('user_management')->where('id', session('user')['userId'])->first();

        return response()->json([
            'success' => true,
            'comment' => [
                'id' => $id,
                'content' => $validated['content'],
                'user_name' => $user->fullName,
                'created_at' => now()->format('Y-m-d H:i:s'),
                'selection_id' => $validated['selection_id']
            ]
        ]);
    }

    public function deleteComment(Request $request)
    {
        DB::table('ebmr_template_comments')->where('id', $request->id)->delete();
        return response()->json(['success' => true]);
    }


    private function injectContent(&$field, $block, $contentBlocks, $lang = 'vi')
    {
        if (!$contentBlocks || empty($block->content)) return;

        // Group content blocks by ID for easy lookup if needed, 
        // though we mostly rely on the placeholders in the HTML structure.
        $cbMap = $contentBlocks->keyBy('id');

        // 1. Rebuild the full HTML by replacing placeholders with text
        $fullHtml = $block->content;
        
        // We will store the IDs found in the placeholders to inject them into the data structure later
        $placeholdersFound = [];

        foreach ($contentBlocks as $cb) {
            $placeholder = "[[CONTENT_$cb->id]]";
            
            if (strpos($fullHtml, $placeholder) !== false) {
                $placeholdersFound[$placeholder] = $cb->id;
            }

            $text = '';
            if ($lang === 'en') {
                $text = $cb->en_contents ?? '';
            } elseif ($lang === 'dual') {
                $vi = $cb->vi_contents ?? '';
                $en = $cb->en_contents ?? '';
                $text = $vi . ($en ? "<br><em class='text-muted small' style='display:block; border-top: 1px dashed #eee; margin-top: 4px;'>$en</em>" : "");
            } else {
                $text = $cb->vi_contents ?? '';
            }

            $fullHtml = str_replace($placeholder, $text, $fullHtml);
        }

        if ($block->type === 'static-text') {
            // Extract the inner HTML from <div class="static-text-display">...</div>
            if (preg_match('/<div class="static-text-display"[^>]*>(.*?)<\/div>/is', $fullHtml, $matches)) {
                $field['content'] = $matches[1];
            } else {
                $field['content'] = $block->content;
            }

            $contentBlock = $contentBlocks->first();
            if ($contentBlock) {
                $field['content_db_id'] = $contentBlock->id;
            }
        } elseif ($block->type === 'table') {
            $rows = $field['rows'] ?? 0;
            $cols = $field['cols'] ?? 0;

            for ($r = 0; $r < $rows; $r++) {
                if (!isset($field['data'][$r])) continue;
                for ($c = 0; $c < $cols; $c++) {
                    if (!isset($field['data'][$r][$c]) || !is_array($field['data'][$r][$c])) continue;
                    
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
                            $text = $vi . ($en ? "<br><em class='text-muted small' style='display:block; border-top: 1px dashed #eee; margin-top: 4px;'>$en</em>" : "");
                        } else {
                            $text = $cb->vi_contents ?? '';
                        }
                        $cell['content'] = $text;
                    }
                }
            }
        }
    }

    private function generateTableHtmlStructure($field, $blockId, $templateId, $sectionId, $lang, $existingContent = null)
    {
        $rows = $field['rows'] ?? 0;
        $cols = $field['cols'] ?? 0;
        $columns = $field['columns'] ?? [];
        $hideHeader = $field['hideHeader'] ?? false;
        $touchedIds = [];

        $html = '<table class="mini-table">';

        if (!$hideHeader) {
            $html .= '<thead><tr>';
            foreach ($columns as $col) {
                $html .= '<th>' . ($col['label'] ?? '') . '</th>';
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
                    'updated_at' => now()
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

                if (!$hidden) {
                    $style = '';
                    if (is_array($cellData)) {
                        if (!empty($cellData['backgroundColor'])) $style .= "background-color:{$cellData['backgroundColor']};";
                        if (!empty($cellData['textAlign'])) $style .= "text-align:{$cellData['textAlign']};";
                        if (!empty($cellData['fontWeight'])) $style .= "font-weight:{$cellData['fontWeight']};";
                        if (!empty($cellData['fontStyle'])) $style .= "font-style:{$cellData['fontStyle']};";
                    }

                    $html .= '<td' . ($rs > 1 ? ' rowspan="' . $rs . '"' : '') . ($cs > 1 ? ' colspan="' . $cs . '"' : '') . ($style ? ' style="' . $style . '"' : '') . '>';
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
            'updatedField' => $field
        ];
    }

    private function splitHtmlAndText($html, $placeholder)
    {
        // If it's empty or null
        if (empty($html)) return ['', ''];

        // Get clean text
        $text = trim(strip_tags($html));

        // If there's no text (only tags), just return as is
        if ($text === '') return [$html, ''];

        // We want to replace the FIRST occurrence of text content with the placeholder,
        // and remove all other text nodes to preserve HTML structure while fully isolating text.
        $first = true;
        $structure = preg_replace_callback('/>([^<]+)</', function ($matches) use (&$first, $placeholder) {
            if (trim($matches[1]) !== '') {
                if ($first) {
                    $first = false;
                    return '>' . $placeholder . '<';
                } else {
                    return '><'; // Clear subsequent text nodes
                }
            }
            return $matches[0];
        }, '>' . $html . '<'); // wrap in >< to catch text at the very beginning/end

        $structure = substr($structure, 1, -1);

        return [$structure, $text];
    }

    public function aiTranslate(Request $request)
    {
        $startTimeTotal = microtime(true);
        Log::info("--- AI Translation Started ---");
        try {
            $templateId = $request->template_id;
            $lang = $request->lang ?? 'en'; // Target language

            $contents = DB::table('ebmr_content_blocks')
                ->where('template_id', $templateId)
                ->whereNotNull('vi_contents')
                ->where('vi_contents', '!=', '')
                ->whereRaw("TRIM(REPLACE(vi_contents, CHAR(160), '')) != ''") // Exclude blocks that are only nbsp or whitespace
                ->where(function ($q) {
                    $q->whereNull('en_contents')->orWhere('en_contents', '');
                })
                ->get();

            if ($contents->isEmpty()) {
                Log::info("AI Translation: No new content to translate.");
                return response()->json(['success' => false, 'message' => 'Không có nội dung mới để dịch (hoặc nội dung chỉ chứa khoảng trắng)']);
            }

            Log::info("AI Translation: Found " . $contents->count() . " blocks to translate.");

            // Clean strings in memory too
            foreach ($contents as $cb) {
                $cb->vi_contents = trim(str_replace(["\xc2\xa0", "&nbsp;"], ' ', $cb->vi_contents));
            }

            $allTranslatedArray = [];
            $chunkSize = 15;
            $chunks = $contents->chunk($chunkSize); 
            $totalChunks = $chunks->count();
            Log::info("AI Translation: Processing in $totalChunks chunks (Batch size: $chunkSize).");

            foreach ($chunks as $chunkIndex => $chunk) {
                $chunkStartTime = microtime(true);
                $textsToTranslate = $chunk->pluck('vi_contents')->toArray();
                
                Log::info("AI Translation: [Chunk " . ($chunkIndex + 1) . "/$totalChunks] Sending " . count($textsToTranslate) . " items to Ollama...");

                $prompt = "You are a professional pharmaceutical translator. 
                Translate the following list of Vietnamese strings into accurate technical English for a pharmaceutical Batch Manufacturing Record (BMR).
                Return ONLY a JSON array of strings in the EXACT same order.
                Input: " . json_encode($textsToTranslate, JSON_UNESCAPED_UNICODE);

                $ollamaUrl = env('OLLAMA_URL', 'http://localhost:11434');
                $ollamaModel = env('OLLAMA_MODEL', 'qwen2.5:14b');

                $ollamaStartTime = microtime(true);
                $response = \Illuminate\Support\Facades\Http::timeout(180)->withoutVerifying()
                    ->post("$ollamaUrl/api/generate", [
                        'model' => $ollamaModel,
                        'prompt' => $prompt,
                        'stream' => false,
                        'format' => 'json'
                    ]);
                $ollamaEndTime = microtime(true);
                $ollamaDuration = round($ollamaEndTime - $ollamaStartTime, 2);

                if ($response->failed()) {
                    Log::error("AI Translation: Ollama request failed after {$ollamaDuration}s. Error: " . $response->body());
                    return response()->json(['success' => false, 'message' => 'Ollama Error: ' . $response->body()]);
                }

                $resData = $response->json();
                $translatedText = $resData['response'] ?? null;
                $translatedArray = json_decode($translatedText, true);

                Log::info("AI Translation: [Chunk " . ($chunkIndex + 1) . "] Received response from Ollama in {$ollamaDuration}s.");

                // Find array in response if wrapped
                if (is_array($translatedArray) && !isset($translatedArray[0])) {
                    foreach ($translatedArray as $val) {
                        if (is_array($val) && count($val) === count($textsToTranslate)) {
                            $translatedArray = $val;
                            break;
                        }
                    }
                }

                if (!is_array($translatedArray) || count($translatedArray) !== count($textsToTranslate)) {
                    Log::error("AI Translation: [Chunk " . ($chunkIndex + 1) . "] Data mismatch. Expected: " . count($textsToTranslate) . ", Received: " . (is_array($translatedArray) ? count($translatedArray) : 0));
                    return response()->json([
                        'success' => false, 
                        'message' => 'Lỗi khớp dữ liệu tại cụm tin nhắn. AI dịch thiếu hoặc sai định dạng.',
                        'expected_count' => count($textsToTranslate),
                        'received_count' => is_array($translatedArray) ? count($translatedArray) : 0,
                        'raw' => $translatedText
                    ]);
                }

                $allTranslatedArray = array_merge($allTranslatedArray, $translatedArray);
                $chunkEndTime = microtime(true);
                $chunkDuration = round($chunkEndTime - $chunkStartTime, 2);
                Log::info("AI Translation: [Chunk " . ($chunkIndex + 1) . "] Completed in {$chunkDuration}s.");
            }

            // Update database with AI translations
            Log::info("AI Translation: Updating database with " . count($allTranslatedArray) . " translations...");
            foreach ($contents as $index => $cb) {
                DB::table('ebmr_content_blocks')
                    ->where('id', $cb->id)
                    ->update([
                        'en_contents' => $allTranslatedArray[$index] ?? '',
                        'updated_at' => now()
                    ]);
            }

            $endTimeTotal = microtime(true);
            $durationTotal = round($endTimeTotal - $startTimeTotal, 2);
            Log::info("--- AI Translation Finished successfully in {$durationTotal}s ---");

            return response()->json(['success' => true, 'count' => count($allTranslatedArray), 'duration' => $durationTotal]);
        } catch (\Exception $e) {
            Log::error("AI Translation Exception: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
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

            Log::info("AI Single Translation: Starting for " . $blocks->count() . " blocks.");

            $textsToTranslate = $blocks->pluck('vi_contents')->toArray();
            
            // Filter out empty or whitespace-only strings
            $filteredTexts = [];
            $mapping = [];
            foreach ($textsToTranslate as $index => $text) {
                $clean = trim(str_replace(["\xc2\xa0", "&nbsp;"], ' ', $text));
                if ($clean !== '') {
                    $filteredTexts[] = $clean;
                    $mapping[] = $index;
                }
            }

            if (empty($filteredTexts)) {
                return response()->json(['success' => false, 'message' => 'Nội dung trống, không cần dịch.']);
            }

            $prompt = "You are a professional pharmaceutical translator. 
            Translate the following list of Vietnamese strings into accurate technical English for a pharmaceutical Batch Manufacturing Record (BMR).
            Return ONLY a JSON array of strings in the EXACT same order.
            Input: " . json_encode($filteredTexts, JSON_UNESCAPED_UNICODE);

            $ollamaUrl = env('OLLAMA_URL', 'http://localhost:11434');
            $ollamaModel = env('OLLAMA_MODEL', 'qwen2.5:14b');

            $response = \Illuminate\Support\Facades\Http::timeout(180)->withoutVerifying()
                ->post("$ollamaUrl/api/generate", [
                    'model' => $ollamaModel,
                    'prompt' => $prompt,
                    'stream' => false,
                    'format' => 'json'
                ]);

            if ($response->failed()) {
                return response()->json(['success' => false, 'message' => 'Ollama Error: ' . $response->body()]);
            }

            $resData = $response->json();
            $translatedText = $resData['response'] ?? null;
            $translatedArray = json_decode($translatedText, true);

            // Find array in response if wrapped
            if (is_array($translatedArray) && !isset($translatedArray[0])) {
                foreach ($translatedArray as $val) {
                    if (is_array($val) && count($val) === count($filteredTexts)) {
                        $translatedArray = $val;
                        break;
                    }
                }
            }

            if (!is_array($translatedArray) || count($translatedArray) !== count($filteredTexts)) {
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
                        'updated_at' => now()
                    ]);
            }

            $duration = round(microtime(true) - $startTime, 2);
            Log::info("AI Single Translation: Finished in {$duration}s.");

            return response()->json([
                'success' => true, 
                'count' => count($translatedArray),
                'translations' => $translatedArray,
                'mapping' => $mapping,
                'ids' => $blocks->pluck('id')->toArray()
            ]);

        } catch (\Exception $e) {
            Log::error("AI Single Translation Error: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
