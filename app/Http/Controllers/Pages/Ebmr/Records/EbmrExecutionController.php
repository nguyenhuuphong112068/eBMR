<?php

namespace App\Http\Controllers\Pages\Ebmr\Records;

use App\Http\Controllers\Controller;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Services\RunDataEncryptionService;
use Carbon\Carbon;

class EbmrExecutionController extends Controller
{
    /**
     * List of issued records (Batch Records)
     */
    public function index(Request $request)
    {
        $mode = $request->query('mode', 'working');
        if ($mode == 'completed') {
            $title = 'Hồ Sơ Hoàn Thành';
        } elseif ($mode == 'history') {
            $title = 'Lịch Sử Ban Hành BMR';
        } else {
            $title = 'Hồ Sơ Đã Nhận Ban Hành';
        }

        session(['title' => $title]);

        $query = DB::table('ebmr_records')
            ->join('ebmr_templates', 'ebmr_records.template_id', '=', 'ebmr_templates.id')
            ->leftJoin('user_management', 'ebmr_records.created_by', '=', 'user_management.id')
            ->select('ebmr_records.*', 'user_management.fullName as issuer_name', 'ebmr_templates.type', 'ebmr_templates.caterogy_id');

        if ($mode == 'completed') {
            $query->whereIn('ebmr_records.status', ['completed', 'reviewed']);
        } elseif ($mode != 'history') {
            // Working mode
            $query->whereNotIn('ebmr_records.status', ['completed', 'reviewed']);
        }

        $records = $query->orderBy('ebmr_records.created_at', 'desc')->get();

        foreach ($records as $r) {
            if ($r->type === 'GF') {
                $r->template_name = DB::table('gf_category')->where('id', $r->caterogy_id)->value('name') ?? 'N/A';
                $r->document_code = DB::table('gf_category')->where('id', $r->caterogy_id)->value('code') ?? 'N/A';
            } elseif ($r->type === 'MF') {
                $r->template_name = DB::table('mf_category')->where('id', $r->caterogy_id)->value('name') ?? 'N/A';
                $r->document_code = DB::table('mf_category')->where('id', $r->caterogy_id)->value('code') ?? 'N/A';
            } elseif ($r->type === 'BPR') {
                $cat = DB::table('finished_product_category')
                    ->leftJoin('product_name', 'finished_product_category.product_name_id', '=', 'product_name.id')
                    ->where('finished_product_category.id', $r->caterogy_id)
                    ->select('finished_product_category.finished_product_code', 'product_name.name')
                    ->first();
                $r->template_name = $cat->name ?? 'N/A';
                $r->document_code = $cat->finished_product_code ?? 'N/A';
            } else {
                $cat = DB::table('intermediate_category')
                    ->leftJoin('product_name', 'intermediate_category.product_name_id', '=', 'product_name.id')
                    ->where('intermediate_category.id', $r->caterogy_id)
                    ->select('intermediate_category.intermediate_code', 'product_name.name')
                    ->first();
                $r->template_name = $cat->name ?? 'N/A';
                $r->document_code = $cat->intermediate_code ?? 'N/A';
            }

            // --- Fetch Stages (Sections) ---
            $r->sections = DB::table('ebmr_template_blocks')
                ->where('template_id', $r->template_id)
                ->where('type', 'section')
                ->orderBy('order')
                ->get()
                ->map(function ($b) {
                    $prop = json_decode($b->properties);
                    return [
                        'id' => $b->section_id,
                        'label' => $prop->label ?? 'N/A'
                    ];
                });

            // --- Trạng thái phân phối hiện tại của từng công đoạn (nếu đã phân phối) ---
            $r->distributions = DB::table('ebmr_record_distributions')
                ->where('record_id', $r->id)
                ->get()
                ->keyBy('section_id');
        }

        return view('pages.ebmr.records.list', [
            'records' => $records,
            'mode' => $mode
        ]);
    }

    /**
     * Dashboard of production stages and room states
     */
    public function productionIndex(Request $request)
    {
        session(['title' => 'Sản Xuất']);

        // 1. Fetch active rooms
        $rooms = DB::connection('pms')->table('room')
            ->where('active', 1)
            ->orderBy('code')
            ->get();

        // 2. Fetch active records (non-completed BMR/BPR) trực tiếp từ ebmr_record_distributions —
        // đây là nguồn sự thật duy nhất cho "công đoạn nào của lô nào đang ở phòng nào". Mỗi dòng
        // kết quả tương ứng đúng 1 công đoạn đã được Phân phối; công đoạn chưa phân phối sẽ không
        // xuất hiện ở bất kỳ phòng nào (không còn dựa vào ebmr_template_rooms — bảng "phòng khả dụng"
        // cấu hình theo template cũ, không đồng nhất với phòng đích thực tế chọn khi Phân phối).
        $activeRecords = DB::table('ebmr_record_distributions')
            ->join('ebmr_records', 'ebmr_record_distributions.record_id', '=', 'ebmr_records.id')
            ->join('ebmr_templates', 'ebmr_records.template_id', '=', 'ebmr_templates.id')
            ->select(
                'ebmr_records.*',
                'ebmr_templates.type',
                'ebmr_templates.caterogy_id',
                'ebmr_record_distributions.section_id',
                'ebmr_record_distributions.section_label',
                'ebmr_record_distributions.room_id',
                'ebmr_record_distributions.id as distribution_id',
                'ebmr_record_distributions.user_ids as distribution_user_ids'
            )
            ->whereNotIn('ebmr_records.status', ['completed', 'reviewed'])
            ->get();

        $currentUserId = (int) (session('user')['userId'] ?? 0);

        // Populate product name + phân phối/ghi chép cho từng hồ sơ đang hoạt động
        foreach ($activeRecords as $r) {
            if ($r->type === 'GF') {
                $r->product_name = DB::table('gf_category')->where('id', $r->caterogy_id)->value('name') ?? 'N/A';
            } elseif ($r->type === 'MF') {
                $r->product_name = DB::table('mf_category')->where('id', $r->caterogy_id)->value('name') ?? 'N/A';
            } elseif ($r->type === 'BPR') {
                $r->product_name = DB::table('finished_product_category')
                    ->leftJoin('product_name', 'finished_product_category.product_name_id', '=', 'product_name.id')
                    ->where('finished_product_category.id', $r->caterogy_id)
                    ->value('product_name.name') ?? 'N/A';
            } else {
                $r->product_name = DB::table('intermediate_category')
                    ->leftJoin('product_name', 'intermediate_category.product_name_id', '=', 'product_name.id')
                    ->where('intermediate_category.id', $r->caterogy_id)
                    ->value('product_name.name') ?? 'N/A';
            }

            // Chỉ được "Ghi chép" khi: đã phân phối công đoạn này cho 1 phòng CỤ THỂ, user hiện tại
            // nằm trong danh sách được phân phối, VÀ phòng + thiết bị trong phòng đã đạt điều kiện
            // vệ sinh + dọn quang. Nếu chưa đủ điều kiện, chỉ được "Xem hồ sơ" (read-only).
            $r->is_distributed = !is_null($r->distribution_id);
            $r->distribution_user_id_list = $r->distribution_user_ids ? (json_decode($r->distribution_user_ids, true) ?: []) : [];
            $isAssigned = in_array($currentUserId, array_map('intval', $r->distribution_user_id_list), true);
            $r->can_write = $r->is_distributed && $isAssigned && $r->room_id && $this->isRoomCleared($r->room_id);
        }

        // Resolve tên người nhận (được phân phối) 1 lần cho toàn bộ danh sách, tránh N+1 query
        $allAssignedUserIds = $activeRecords->flatMap(function ($r) {
            return $r->distribution_user_id_list;
        })->unique()->filter()->values();
        $userNamesById = DB::table('user_management')
            ->whereIn('id', $allAssignedUserIds)
            ->pluck('fullName', 'id');
        foreach ($activeRecords as $r) {
            $names = array_map(function ($uid) use ($userNamesById) {
                return $userNamesById[$uid] ?? null;
            }, $r->distribution_user_id_list);
            $r->assigned_user_names = implode(', ', array_filter($names));
        }

        // Group active records by room_id
        $recordsByRoom = $activeRecords->groupBy('room_id');

        // Fetch all room conditions from local db
        $conditions = DB::table('room_manufactured_condition')->get()->groupBy('room_id');

        $latestEqLogs = DB::table('room_logbooks')
            ->whereNotNull('equipment_id')
            ->select('equipment_id', DB::raw('MAX(id) as max_id'))
            ->groupBy('equipment_id');

        // Fetch all assigned equipments and their latest status
        $equipments = DB::table('equipment_in_room')
            ->join('instrument', 'equipment_in_room.equipment_id', '=', 'instrument.id')
            ->leftJoinSub($latestEqLogs, 'latest_logs', function ($join) {
                $join->on('instrument.id', '=', 'latest_logs.equipment_id');
            })
            ->leftJoin('room_logbooks', 'latest_logs.max_id', '=', 'room_logbooks.id')
            ->select('equipment_in_room.room_id', 'instrument.id', 'instrument.code', 'instrument.name', 'room_logbooks.current_status as eq_status')
            ->get()
            ->groupBy('room_id');

        // Fetch latest room status
        $latestRoomLogs = DB::table('room_logbooks')
            ->whereNull('equipment_id')
            ->select('room_id', DB::raw('MAX(id) as max_id'))
            ->groupBy('room_id');

        $roomStatuses = DB::table('room_logbooks')
            ->joinSub($latestRoomLogs, 'latest_logs', function ($join) {
                $join->on('room_logbooks.id', '=', 'latest_logs.max_id');
            })
            ->select('room_logbooks.room_id', 'current_status as room_status', 'clean_expiry_date')
            ->get()
            ->keyBy('room_id');

        // Map records and condition limits to rooms
        foreach ($rooms as $room) {
            $room->active_records = $recordsByRoom->get($room->id, collect());
            $room->equipments = $equipments->get($room->id, collect());
            
            $log = $roomStatuses->has($room->id) ? $roomStatuses->get($room->id) : null;
            if ($log) {
                if ($log->room_status === 'cleaned' && $log->clean_expiry_date && \Carbon\Carbon::parse($log->clean_expiry_date)->isPast()) {
                    $room->room_status = 'needs_reclean';
                } else {
                    $room->room_status = $log->room_status;
                }
            } else {
                $room->room_status = 'ready';
            }

            $cond = $conditions->has($room->id) ? $conditions->get($room->id)->first() : null;
            $room->limits = [
                'temp_min' => ($cond && $cond->temp_min_1 !== null) ? (float)$cond->temp_min_1 : 20.0,
                'temp_max' => ($cond && $cond->temp_max_1 !== null) ? (float)$cond->temp_max_1 : 25.0,
                'humid_min' => ($cond && $cond->humidity_min_1 !== null) ? (float)$cond->humidity_min_1 : 35.0,
                'humid_max' => ($cond && $cond->humidity_max_1 !== null) ? (float)$cond->humidity_max_1 : 60.0,
                'press_min' => ($cond && ($cond->diff_press_corridor_min ?? $cond->diff_press_pal_min ?? $cond->diff_press_mal_min) !== null)
                    ? (float)($cond->diff_press_corridor_min ?? $cond->diff_press_pal_min ?? $cond->diff_press_mal_min) : 5.0,
                'press_max' => ($cond && ($cond->diff_press_corridor_max ?? $cond->diff_press_pal_max ?? $cond->diff_press_mal_max) !== null)
                    ? (float)($cond->diff_press_corridor_max ?? $cond->diff_press_pal_max ?? $cond->diff_press_mal_max) : 15.0,
            ];
        }

        $stageProductions = \App\Models\StageProduction::orderBy('workshop_code')->orderBy('order_num')->get()->groupBy('workshop_code');
        $workshopsList = $stageProductions->keys();

        // 3. Fetch users for executor selection
        $users = DB::table('user_management')->select('id', 'fullName as name')->orderBy('fullName')->get();

        return view('pages.ebmr.production.index', [
            'rooms' => $rooms,
            'stageProductions' => $stageProductions,
            'workshopsList' => $workshopsList,
            'users' => $users
        ]);
    }

    /**
     * Get real-time room telemetry data from simulated BMS
     */
    public function getBmsData(Request $request)
    {
        $rooms = DB::connection('pms')->table('room')
            ->where('active', 1)
            ->get();

        $telemetries = [];
        $time = time();

        foreach ($rooms as $room) {
            // Generate stable but fluctuating values based on room ID and current timestamp
            $baseTemp = 20.0 + ($room->id % 5) * 0.8;
            $fluctTemp = sin($time / 30.0 + $room->id) * 0.4;
            $temp = number_format($baseTemp + $fluctTemp, 1);

            $baseHumid = 42 + ($room->id % 6) * 1.5;
            $fluctHumid = cos($time / 45.0 + $room->id) * 2;
            $humid = number_format($baseHumid + $fluctHumid, 0);

            $basePressure = 8 + ($room->id % 8);
            $fluctPressure = sin($time / 60.0 + $room->id) * 1;
            $pressure = '+' . number_format($basePressure + $fluctPressure, 0);

            $telemetries[$room->id] = [
                'temperature' => $temp,
                'humidity' => $humid,
                'pressure' => $pressure,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $telemetries
        ]);
    }

    /**
     * Execution interface for a specific record
     */
    public function execute(Request $request, $id)
    {
        session(['title' => 'Ghi Chép Hồ Sơ BMR']);
        $sectionId = $request->query('section');

        $record = DB::table('ebmr_records')->where('id', $id)->first();
        if (!$record) return redirect()->back()->with('error', 'Hồ sơ không tồn tại.');

        $template = DB::table('ebmr_templates')
            ->leftJoin('user_management', 'ebmr_templates.owner_id', '=', 'user_management.id')
            ->leftJoin('designations', 'user_management.designation_id', '=', 'designations.id')
            ->where('ebmr_templates.id', $record->template_id)
            ->select('ebmr_templates.*', 'user_management.fullName as owner_name', 'user_management.signature_image as owner_signature', 'designations.name as owner_designation')
            ->first();
        if (!$template) return redirect()->back()->with('error', 'Mẫu hồ sơ không tồn tại.');

        $template->workflows = DB::table('ebmr_template_workflows')
            ->leftJoin('user_management', 'ebmr_template_workflows.user_id', '=', 'user_management.id')
            ->leftJoin('designations', 'user_management.designation_id', '=', 'designations.id')
            ->where('template_id', $template->id)
            ->orderBy('step_order')
            ->select('ebmr_template_workflows.*', 'user_management.fullName', 'user_management.groupName as title', 'user_management.deparment as department_name', 'user_management.signature_image as signature_image', 'designations.name as designation_name')
            ->get();

        if ($template->type === 'GF') {
            $cat = DB::table('gf_category')->where('id', $template->caterogy_id)->first();
            $template->category_code = $cat->code ?? '';
            $template->category_name = $cat->name ?? '';
            $template->relatived_sop_no = $cat->relatived_sop_no ?? '';
            $template->name = $template->category_name;
            $template->document_code = $template->category_code;
        } elseif ($template->type === 'MF') {
            $cat = DB::table('mf_category')->where('id', $template->caterogy_id)->first();
            $template->category_code = $cat->code ?? '';
            $template->category_name = $cat->name ?? '';
            $template->name = $template->category_name;
            $template->document_code = $template->category_code;
        } elseif ($template->type === 'BPR') {
            $cat = DB::table('finished_product_category')
                ->leftJoin('product_name', 'finished_product_category.product_name_id', '=', 'product_name.id')
                ->where('finished_product_category.id', $template->caterogy_id)
                ->select('finished_product_category.*', 'product_name.name as product_name')
                ->first();
            $template->category_code = $cat->finished_product_code ?? '';
            $template->category_name = $cat->product_name ?? '';
            $template->name = $template->category_name;
            $template->document_code = $template->category_code;
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
            $template->type_name = $cat->type ?? 'Thuốc Kê Đơn';
            $template->batch_size = ($cat->batch_size ?? '') . ' ' . ($cat->unit_batch_size ?? '');
            $template->name = $template->category_name;
            $template->document_code = $template->category_code;
        }

        $fields = [];
        $fieldsConfig = new \stdClass();

        $blocks = DB::table('ebmr_template_blocks')->where('template_id', $template->id)->orderBy('order')->get();

        // Fetch all testing criteria and properties for dynamic reference replacement (main + linked templates)
        $templateIds = [$template->id];
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

        // Fetch content blocks
        $blockIds = $blocks->pluck('id')->toArray();
        $contentBlocks = DB::table('ebmr_content_blocks')->whereIn('ebmr_template_blocks_id', $blockIds)->get()->groupBy('ebmr_template_blocks_id');

        // Load fieldsConfig from the new dedicated table (One row per variable)
        $variants = DB::table('ebmr_variants')->where('template_id', $template->id)->get();
        if ($variants->isNotEmpty()) {
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
        } else if ($blocks->isNotEmpty()) {
            // Fallback for legacy data
            $fieldsConfig = json_decode($blocks->first()->fields_config, true) ?? [];
        } else {
            $fieldsConfig = [];
        }

        $allFields = [];
        foreach ($blocks as $block) {
            $f = json_decode($block->properties, true);
            $this->injectContent($f, $block, $contentBlocks->get($block->id), $testingCriteria, $properties);
            $f['db_id'] = $block->id; // Track DB ID for section matching
            if (isset($f['type']) && $f['type'] === 'linked-template') {
                $linkedTemplateId = $f['template_id'] ?? null;
                if ($linkedTemplateId) {
                    $linkedBlocks = DB::table('ebmr_template_blocks')->where('template_id', $linkedTemplateId)->orderBy('order')->get();

                    // Fetch linked content blocks
                    $lbIds = $linkedBlocks->pluck('id')->toArray();
                    $lContentBlocks = DB::table('ebmr_content_blocks')->whereIn('ebmr_template_blocks_id', $lbIds)->get()->groupBy('ebmr_template_blocks_id');

                    $variantsLink = DB::table('ebmr_variants')->where('template_id', $linkedTemplateId)->get();
                    if ($variantsLink->isNotEmpty()) {
                        $linkedConfig = [];
                        foreach ($variantsLink as $v) {
                            $config = json_decode($v->config, true) ?? [];
                            $linkedConfig[$v->field_key] = array_merge([
                                'id' => $v->field_key,
                                'name' => $v->name,
                                'label' => $v->label,
                                'type' => $v->type,
                                'section_id' => $v->section_id,
                                'block_id' => $v->block_id,
                            ], $config);
                        }
                    } else if ($linkedBlocks->isNotEmpty()) {
                        $linkedConfig = json_decode($linkedBlocks->first()->fields_config, true) ?? [];
                    } else {
                        $linkedConfig = [];
                    }
                    $fieldsConfig = array_merge((array)$fieldsConfig, (array)$linkedConfig);
                    foreach ($linkedBlocks as $lb) {
                        $linkedF = json_decode($lb->properties, true);
                        $this->injectContent($linkedF, $lb, $lContentBlocks->get($lb->id), $testingCriteria, $properties);
                        $linkedF['is_linked'] = true; // Mark as linked if needed by frontend
                        $allFields[] = $linkedF;
                    }
                }
            } else {
                $allFields[] = $f;
            }
        }

        // Dynamically override fieldsConfig from testing criteria
        $fieldsConfig = $this->overrideFieldsConfigWithTesting($fieldsConfig, $testingCriteria);

        // --- Section Filtering Logic ---
        $activeSectionLabel = null;
        if ($sectionId) {
            $blocksQuery = DB::table('ebmr_template_blocks')
                ->where('template_id', $template->id)
                ->where(function ($q) use ($sectionId, $template) {
                    $q->where('section_id', $sectionId)
                        ->orWhere('section_id', $template->caterogy_id);
                })
                ->orderBy('order')
                ->get();

            $bqIds = $blocksQuery->pluck('id')->toArray();
            $bqContentBlocks = DB::table('ebmr_content_blocks')->whereIn('ebmr_template_blocks_id', $bqIds)->get()->groupBy('ebmr_template_blocks_id');

            $activeSectionLabel = null;
            foreach ($blocksQuery as $block) {
                $f = json_decode($block->properties, true);
                $this->injectContent($f, $block, $bqContentBlocks->get($block->id));
                if (isset($f['type']) && $f['type'] === 'section' && $block->section_id == $sectionId) {
                    $activeSectionLabel = $f['label'] ?? 'Phân đoạn';
                }

                if (isset($f['type']) && $f['type'] === 'linked-template') {
                    $linkedTemplateId = $f['template_id'] ?? null;
                    if ($linkedTemplateId) {
                        $linkedBlocks = DB::table('ebmr_template_blocks')->where('template_id', $linkedTemplateId)->orderBy('order')->get();

                        $lbIds = $linkedBlocks->pluck('id')->toArray();
                        $lContentBlocks = DB::table('ebmr_content_blocks')->whereIn('ebmr_template_blocks_id', $lbIds)->get()->groupBy('ebmr_template_blocks_id');

                        if ($linkedBlocks->isNotEmpty()) {
                            $variantsLink2 = DB::table('ebmr_variants')->where('template_id', $linkedTemplateId)->get();
                            if ($variantsLink2->isNotEmpty()) {
                                $linkedConfig = [];
                                foreach ($variantsLink2 as $v) {
                                    $config = json_decode($v->config, true) ?? [];
                                    $linkedConfig[$v->field_key] = array_merge([
                                        'id' => $v->field_key,
                                        'name' => $v->name,
                                        'label' => $v->label,
                                        'type' => $v->type,
                                        'section_id' => $v->section_id,
                                        'block_id' => $v->block_id,
                                    ], $config);
                                }
                            } else {
                                $linkedConfig = json_decode($linkedBlocks->first()->fields_config, true) ?? [];
                            }
                            $fieldsConfig = array_merge((array)$fieldsConfig, (array)$linkedConfig);
                        }
                        foreach ($linkedBlocks as $lb) {
                            $linkedF = json_decode($lb->properties, true);
                            $this->injectContent($linkedF, $lb, $lContentBlocks->get($lb->id));
                            $linkedF['is_linked'] = true;
                            $fields[] = $linkedF;
                        }
                    }
                } else {
                    $fields[] = $f;
                }
            }
        } else {
            // Use the already fetched allFields if no section filtering
            $fields = $allFields;
        }

        $fieldsConfig = (object)$fieldsConfig;

        // Lấy dữ liệu và gộp lại theo block_uuid (Khởi tạo là object để tránh lỗi JSON array trong JS)
        $runDataRaw = DB::table('ebmr_run_data')->where('record_id', $id)->get();
        
        $historyCounts = DB::table('ebmr_run_data_history')
            ->select('ebmr_run_data_id', DB::raw('count(*) as count'))
            ->where('record_id', $id)
            ->groupBy('ebmr_run_data_id')
            ->pluck('count', 'ebmr_run_data_id');

        $executionValues = (object)[];
        foreach ($runDataRaw as $rd) {
            $blockUuid = $rd->block_uuid;
            if (!isset($executionValues->$blockUuid)) {
                $executionValues->$blockUuid = (object)[];
            }

            // Khởi tạo đối tượng meta nếu chưa có
            if (!isset($executionValues->$blockUuid->_meta)) {
                $executionValues->$blockUuid->_meta = (object)[];
            }

            $cellId = ($rd->cell_id && $rd->cell_id !== 'default') ? $rd->cell_id : 'default';
            // Giải mã raw_value (data cũ chưa mã hoá sẽ tự fallback)
            $decryptedVal = RunDataEncryptionService::decrypt($rd->raw_value);
            if (is_string($decryptedVal) && (str_starts_with(trim($decryptedVal), '{') || str_starts_with(trim($decryptedVal), '['))) {
                $decoded = json_decode($decryptedVal, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $decryptedVal = $decoded;
                }
            }
            $executionValues->$blockUuid->$cellId = $decryptedVal;

            // Lưu metadata
            $executionValues->$blockUuid->_meta->$cellId = (object)[
                'by' => $rd->updated_by,
                'at' => $rd->updated_at ? \Carbon\Carbon::parse($rd->updated_at)->format('d/m/Y H:i') : null,
                'history_count' => $historyCounts[$rd->id] ?? 0
            ];
        }

        $template->schema = (object)['fields' => $fields, 'fieldsConfig' => $fieldsConfig];

        $isReadOnly = $this->computeIsReadOnly($request, $record);

        return view('pages.ebmr.execute', [
            'record' => $record,
            'template' => $template,
            'executionValues' => $executionValues,
            'isExecutionMode' => true,
            'isReadOnly' => $isReadOnly,
            'activeSectionId' => $sectionId,
            'activeSectionLabel' => $activeSectionLabel
        ]);
    }

    /**
     * Hồ sơ đã hoàn thành/đã duyệt thì luôn chỉ xem. Nếu hồ sơ CHƯA từng được
     * "Phân phối" (bảng ebmr_record_distributions rỗng cho record này), giữ
     * nguyên hành vi cũ: cho ghi chép trực tiếp — không phá vỡ các hồ sơ/luồng
     * chưa dùng tính năng phân phối. Một khi ĐÃ phân phối, mặc định chỉ xem;
     * chỉ mở khóa ghi chép khi truy cập đúng ngữ cảnh phân phối hợp lệ (?dist=)
     * VÀ user hiện tại nằm trong danh sách được phân phối VÀ phòng đã đạt điều
     * kiện vệ sinh + dọn quang.
     */
    private function computeIsReadOnly(Request $request, $record)
    {
        if (in_array($record->status, ['completed', 'reviewed'])) {
            return true;
        }

        $hasDistribution = DB::table('ebmr_record_distributions')->where('record_id', $record->id)->exists();
        if (!$hasDistribution) {
            return false;
        }

        $distId = $request->query('dist');
        if (!$distId) {
            return true;
        }

        $dist = DB::table('ebmr_record_distributions')->where('id', $distId)->where('record_id', $record->id)->first();
        if (!$dist) {
            return true;
        }

        $userIds = array_map('intval', json_decode($dist->user_ids ?? '[]', true) ?: []);
        $currentUserId = (int) (session('user')['userId'] ?? 0);
        $isAssigned = in_array($currentUserId, $userIds, true);

        return !($isAssigned && $this->isRoomCleared($dist->room_id));
    }

    /**
     * Kiểm tra phòng (và toàn bộ thiết bị được khai báo trong phòng) đã đạt cả
     * 2 điều kiện: (a) nhãn vệ sinh gần nhất còn hiệu lực (current_status =
     * 'cleaned', chưa quá clean_expiry_date), (b) đã có 1 chiến dịch dọn quang
     * (clearance) hoàn tất cho phòng/thiết bị đó.
     */
    private function isRoomCleared($roomId)
    {
        if (!$roomId) return false;

        $roomLog = DB::table('room_logbooks')
            ->where('room_id', $roomId)
            ->whereNull('equipment_id')
            ->orderByDesc('id')
            ->first();
        if (!$roomLog || $roomLog->current_status !== 'cleaned') return false;
        if ($roomLog->clean_expiry_date && Carbon::parse($roomLog->clean_expiry_date)->isPast()) return false;

        $roomClearanceDone = DB::table('clearance_room_campaigns')
            ->where('room_id', $roomId)
            ->where('status', 'completed')
            ->exists();
        if (!$roomClearanceDone) return false;

        $equipIds = DB::table('equipment_in_room')->where('room_id', $roomId)->pluck('equipment_id');
        foreach ($equipIds as $equipId) {
            $eqLog = DB::table('room_logbooks')
                ->where('equipment_id', $equipId)
                ->orderByDesc('id')
                ->first();
            if (!$eqLog || $eqLog->current_status !== 'cleaned') return false;
            if ($eqLog->clean_expiry_date && Carbon::parse($eqLog->clean_expiry_date)->isPast()) return false;

            $eqClearanceDone = DB::table('clearance_equip_campaigns')
                ->where('equipment_id', $equipId)
                ->where('status', 'completed')
                ->exists();
            if (!$eqClearanceDone) return false;
        }

        return true;
    }

    /**
     * Danh sách phòng (active) để chọn khi phân phối công đoạn.
     */
    public function getRoomOptions()
    {
        // Trả kèm stage_code để frontend tự lọc phòng theo công đoạn (client-side,
        // tránh gọi API riêng cho từng công đoạn khi mở modal Phân phối).
        $rooms = DB::connection('pms')->table('room')
            ->where('active', 1)
            ->orderBy('code')
            ->select('id', 'code', 'name', 'deparment_code', 'stage_code')
            ->get();

        return response()->json($rooms);
    }

    /**
     * Danh sách người dùng để chọn khi phân phối, lọc theo phân xưởng (deparment_code)
     * của sản phẩm/hồ sơ này — chỉ những người thuộc đúng phân xưởng mới hiện ra.
     * BMR (intermediate_category) và BPR (finished_product_category) có mã phân xưởng;
     * GF/MF hiện chưa có khái niệm này ở category nên trả về toàn bộ user (không lọc).
     */
    public function getRecordWorkshopUsers($recordId)
    {
        $record = DB::table('ebmr_records')->where('id', $recordId)->first();
        if (!$record) return response()->json([]);

        $template = DB::table('ebmr_templates')->where('id', $record->template_id)->first();
        if (!$template) return response()->json([]);

        $deparmentCode = null;
        if ($template->type === 'BPR') {
            $deparmentCode = DB::table('finished_product_category')->where('id', $template->caterogy_id)->value('deparment_code');
        } elseif (!in_array($template->type, ['GF', 'MF'])) {
            // Mặc định còn lại (BMR/CO) dùng intermediate_category
            $deparmentCode = DB::table('intermediate_category')->where('id', $template->caterogy_id)->value('deparment_code');
        }

        $query = DB::table('user_management')->select('id', 'fullName as name', 'deparment');
        if ($deparmentCode) {
            $query->where('deparment', $deparmentCode);
        }

        return response()->json($query->orderBy('fullName')->get());
    }

    /**
     * Trạng thái phân phối hiện tại của 1 hồ sơ (để hiển thị lại khi mở lại modal Phân phối).
     */
    public function getRecordDistribution($recordId)
    {
        $rows = DB::table('ebmr_record_distributions')
            ->where('record_id', $recordId)
            ->get()
            ->map(function ($d) {
                $d->user_ids = json_decode($d->user_ids ?? '[]', true) ?: [];
                return $d;
            });

        return response()->json($rows);
    }

    /**
     * Phân phối từng công đoạn (section) của 1 lô đến 1 phòng sản xuất cụ thể,
     * kèm danh sách user được phép ghi chép công đoạn đó của lô này.
     */
    public function distributeSections(Request $request)
    {
        $validated = $request->validate([
            'record_id' => 'required|integer',
            'distributions' => 'required|array',
            'distributions.*.section_id' => 'required|string',
            'distributions.*.section_label' => 'nullable|string',
            'distributions.*.room_id' => 'nullable|integer',
            'distributions.*.user_ids' => 'nullable|array',
        ]);

        $recordId = $validated['record_id'];
        $record = DB::table('ebmr_records')->where('id', $recordId)->first();
        if (!$record) {
            return response()->json(['success' => false, 'message' => 'Hồ sơ không tồn tại']);
        }

        $distributedBy = session('user')['userId'] ?? null;
        $now = now();
        $count = 0;

        foreach ($validated['distributions'] as $dist) {
            if (empty($dist['room_id'])) continue; // Bỏ qua công đoạn chưa chọn phòng

            $room = DB::connection('pms')->table('room')->where('id', $dist['room_id'])->first();

            $payload = [
                'section_label' => $dist['section_label'] ?? null,
                'room_id' => $dist['room_id'],
                'room_code' => $room->code ?? null,
                'room_name' => $room->name ?? null,
                'user_ids' => json_encode(array_values($dist['user_ids'] ?? [])),
                'distributed_by' => $distributedBy,
                'updated_at' => $now,
            ];

            $existing = DB::table('ebmr_record_distributions')
                ->where('record_id', $recordId)
                ->where('section_id', $dist['section_id'])
                ->first();

            if ($existing) {
                DB::table('ebmr_record_distributions')->where('id', $existing->id)->update($payload);
            } else {
                DB::table('ebmr_record_distributions')->insert(array_merge($payload, [
                    'record_id' => $recordId,
                    'section_id' => $dist['section_id'],
                    'created_at' => $now,
                ]));
            }
            $count++;
        }

        if ($count === 0) {
            return response()->json(['success' => false, 'message' => 'Vui lòng chọn ít nhất 1 phòng cho 1 công đoạn.']);
        }

        return response()->json(['success' => true, 'message' => "Đã phân phối $count công đoạn thành công"]);
    }

    /**
     * Update the record data during execution
     */
    public function updateRecordData(Request $request)
    {
        Log::info('--- SAVE ATTEMPT ---');
        Log::info($request->all());

        $validated = $request->validate([
            'record_id' => 'required',
            'data' => 'nullable',
            'status' => 'nullable|string'
        ]);

        $userId = session('user')['userId'] ?? 1;
        $now = now();
        $dataEntries = $request->input('data') ?? [];
        $reasons = is_string($request->input('reasons')) ? json_decode($request->input('reasons'), true) : ($request->input('reasons') ?? []);
        Log::info("Data Entries to process: " . count($dataEntries));
        DB::beginTransaction();
        try {
            if (!empty($validated['status'])) {
                DB::table('ebmr_records')
                    ->where('id', $validated['record_id'])

                    ->update(['status' => $validated['status'], 'updated_at' => $now]);
            }

            $userName = session('user')['fullName'] ?? 'System';
            foreach ($dataEntries as $blockUuid => $value) {
                Log::info("Processing block: " . $blockUuid . " with value: " . json_encode($value));
                if (empty($blockUuid)) continue;

                // Nếu value là mảng hoặc đối tượng (dành cho bảng/ô có tọa độ)
                if (is_array($value) || is_object($value)) {
                    foreach ($value as $cellId => $rawValue) {
                        if ($cellId === '_meta') continue;

                        $reason = $reasons[$blockUuid][$cellId] ?? null;

                        $existing = DB::table('ebmr_run_data')->where([
                            'record_id' => $validated['record_id'],
                            'block_uuid' => $blockUuid,
                            'cell_id' => $cellId
                        ])->first();

                        $oldRawValue = null;
                        if ($existing) {
                            $oldRawValue = RunDataEncryptionService::decrypt($existing->raw_value);
                            if ((string)$oldRawValue !== (string)$rawValue && $oldRawValue !== null && $oldRawValue !== "") {
                                if (empty($reason)) {
                                    throw new \Exception("Vui lòng cung cấp lý do thay đổi dữ liệu.");
                                }
                            }
                        }

                        $rawValueStr = (is_array($rawValue) || is_object($rawValue)) ? json_encode($rawValue) : (string)$rawValue;

                        Log::info("Saving cell: " . $cellId . " = " . $rawValueStr);
                        DB::table('ebmr_run_data')->updateOrInsert(
                            [
                                'record_id' => $validated['record_id'],
                                'block_uuid' => $blockUuid,
                                'cell_id' => $cellId
                            ],
                            [
                                'filled_by' => $userId,
                                'filled_at' => $now,
                                'value'     => RunDataEncryptionService::encryptJson([$cellId => $rawValue]),
                                'raw_value' => RunDataEncryptionService::encrypt($rawValueStr),
                                'updated_at' => $now,
                                'updated_by' => $userName,
                            ]
                        );

                        if ($existing && (string)$oldRawValue !== (string)$rawValue) {
                            DB::table('ebmr_run_data_history')->insert([
                                'ebmr_run_data_id' => $existing->id,
                                'record_id' => $validated['record_id'],
                                'block_uuid' => $blockUuid,
                                'cell_id' => $cellId,
                                'old_raw_value' => $existing->raw_value,
                                'new_raw_value' => RunDataEncryptionService::encrypt($rawValueStr),
                                'reason' => $reason,
                                'changed_by' => $userName,
                                'changed_at' => $now,
                                'created_at' => $now,
                                'updated_at' => $now,
                            ]);
                        }
                    }
                } else {
                    $reason = $reasons[$blockUuid]['default'] ?? null;

                    $existing = DB::table('ebmr_run_data')->where([
                        'record_id' => $validated['record_id'],
                        'block_uuid' => $blockUuid,
                        'cell_id' => 'default'
                    ])->first();

                    $oldRawValue = null;
                    if ($existing) {
                        $oldRawValue = RunDataEncryptionService::decrypt($existing->raw_value);
                        if ((string)$oldRawValue !== (string)$value && $oldRawValue !== null && $oldRawValue !== "") {
                            if (empty($reason)) {
                                throw new \Exception("Vui lòng cung cấp lý do thay đổi dữ liệu.");
                            }
                        }
                    }

                    Log::info("Saving direct value for block: " . $blockUuid);
                    // Nếu là giá trị đơn
                    DB::table('ebmr_run_data')->updateOrInsert(
                        [
                            'record_id' => $validated['record_id'],
                            'block_uuid' => $blockUuid,
                            'cell_id' => 'default'
                        ],
                        [
                            'filled_by' => $userId,
                            'filled_at' => $now,
                            'value'     => RunDataEncryptionService::encryptJson(['text' => $value]),
                            'raw_value' => RunDataEncryptionService::encrypt((string)$value),
                            'updated_at' => $now,
                            'updated_by' => $userName,
                        ]
                    );

                    if ($existing && (string)$oldRawValue !== (string)$value) {
                        DB::table('ebmr_run_data_history')->insert([
                            'ebmr_run_data_id' => $existing->id,
                            'record_id' => $validated['record_id'],
                            'block_uuid' => $blockUuid,
                            'cell_id' => 'default',
                            'old_raw_value' => $existing->raw_value,
                            'new_raw_value' => RunDataEncryptionService::encrypt((string)$value),
                            'reason' => $reason,
                            'changed_by' => $userName,
                            'changed_at' => $now,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }
                }
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Lưu dữ liệu phân rã thành công',
                'updated_by' => $userName,
                'updated_at' => $now->format('d/m/Y H:i')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Lỗi Database: ' . $e->getMessage()]);
        }
    }

    /**
     * Get run data history for a specific cell
     */
    public function getRunDataHistory($record_id, $block_uuid, $cell_id)
    {
        $history = DB::table('ebmr_run_data_history')
            ->where('record_id', $record_id)
            ->where('block_uuid', $block_uuid)
            ->where('cell_id', $cell_id)
            ->orderBy('changed_at', 'desc')
            ->get();

        $formattedHistory = [];
        $count = $history->count();
        foreach ($history as $idx => $h) {
            $formattedHistory[] = [
                'change_index' => $count - $idx,
                'old_value' => RunDataEncryptionService::decrypt($h->old_raw_value),
                'new_value' => RunDataEncryptionService::decrypt($h->new_raw_value),
                'reason' => $h->reason,
                'changed_by' => $h->changed_by,
                'changed_at' => \Carbon\Carbon::parse($h->changed_at)->format('d/m/Y H:i:s')
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $formattedHistory
        ]);
    }

    /**
     * Verify user password for electronic signature
     */
    public function verifyPassword(Request $request)
    {
        $password = $request->password;
        
        if ($request->has('username') && $request->username) {
            $username = $request->username;
        } else {
            $userSession = session('user');
            $username = $userSession['username'] ?? $userSession['user_name'] ?? $userSession['userName'] ?? null;
        }

        if (!$username) {
            return response()->json(['success' => false, 'message' => 'Không thể xác định tài khoản.']);
        }

        $user = DB::table('user_management')->where('userName', $username)->first();
        if ($user && Hash::check($password, $user->passWord)) {
            return response()->json([
                'success' => true,
                'fullName' => $user->fullName,
                'signature_image' => $user->signature_image
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Tài khoản hoặc mật khẩu xác nhận không chính xác.']);
    }

    /**
     * Verify another user's credentials (e.g. for checker role) and return their full name
     */
    public function verifyChecker(Request $request)
    {
        $username = $request->username;
        $password = $request->password;

        if (!$username || !$password) {
            return response()->json(['success' => false, 'message' => 'Vui lòng nhập tài khoản và mật khẩu.']);
        }

        $user = DB::table('user_management')->where('userName', $username)->first();
        if ($user && Hash::check($password, $user->passWord)) {
            return response()->json([
                'success' => true,
                'fullName' => $user->fullName ?? $user->userName,
                'signature_image' => $user->signature_image
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Tài khoản hoặc mật khẩu không chính xác.']);
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

        if (!$contentBlocks || empty($block->content)) return;

        // 1. Rebuild the full HTML by replacing placeholders with text
        $fullHtml = $block->content;
        foreach ($contentBlocks as $cb) {
            $placeholder = "[[CONTENT_$cb->id]]";
            $text = $cb->vi_contents ?? '';
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
            $content = preg_replace_callback('/\{\{(field_[a-zA-Z0-9_]+)\}\}/', function ($m) {
                return '<span contenteditable="false" class="ebmr-field-badge" data-field-id="' . $m[1] . '" onclick="selectField(event, \'' . $m[1] . '\')"></span>';
            }, $content);

            $field['content'] = $content;
        } elseif ($block->type === 'table') {
            $rows = $field['rows'] ?? 0;
            $cols = $field['cols'] ?? 0;
            $cbMap = $contentBlocks ? $contentBlocks->keyBy('id') : collect();

            if (!isset($field['data'])) $field['data'] = [];
            for ($r = 0; $r < $rows; $r++) {
                if (!isset($field['data'][$r])) $field['data'][$r] = [];
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
                                return '<span contenteditable="false" class="ebmr-field-badge" data-field-id="' . $m[1] . '" onclick="selectField(event, \'' . $m[1] . '\')"></span>';
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

    /**
     * Tra cứu và hiển thị tài liệu PDF từ thư mục chia sẻ mạng theo mã tài liệu (đã được tối ưu bằng cách truy vấn DB trước để tìm ID)
     */
    public function viewDocumentByCode(Request $request, $code)
    {
        $basePath = "\\\\10.71.1.57\\inetpub\\wwwroot\\ATLDOCPRO500_PQ\\DOCPROVIEWER500\\STELLA\\Documents";

        // Kiểm tra xem thư mục gốc có tồn tại và truy cập được không
        if (!is_dir($basePath)) {
            Log::error("Không thể truy cập thư mục mạng: " . $basePath);
            return response()->view('errors.document_error', [
                'message' => 'Không thể kết nối tới máy chủ lưu trữ tài liệu (thư mục chia sẻ mạng không khả dụng).'
            ], 500);
        }

        // Giải mã URL trong trường hợp mã tài liệu chứa các ký tự đặc biệt được encode
        $decodedCode = urldecode($code);

        // Chuẩn hóa mã tài liệu để lọc PHP (chuyển chữ thường, loại bỏ ký tự đặc biệt)
        $normalizedUserCode = preg_replace('/[^a-z0-9]/', '', strtolower($decodedCode));
        if (empty($normalizedUserCode)) {
            return response()->view('errors.document_error', [
                'message' => 'Mã tài liệu tìm kiếm không hợp lệ.'
            ], 400);
        }

        // Làm sạch mã tài liệu trước khi tìm kiếm DB (loại bỏ khoảng trắng và các ký tự đặc biệt dư thừa ở đầu và cuối chuỗi)
        $cleanedCode = trim($decodedCode);
        $cleanedCode = preg_replace('/^[^a-zA-Z0-9]+|[^a-zA-Z0-9]+$/', '', $cleanedCode);

        // 1. Truy vấn database Doc để lấy thông tin tài liệu có mã tương ứng (sử dụng LIKE linh hoạt dựa trên chuỗi đã làm sạch)
        try {
            $likePattern = '%' . str_replace(['-', '_', ' '], '%', $cleanedCode) . '%';
            $records = DB::connection('Doc')
                ->table('Document_records')
                ->where('Document_Code', 'like', $likePattern)
                ->get();

            // Lọc chính xác mã tài liệu trên PHP
            $matchedRecords = $records->filter(function ($r) use ($normalizedUserCode) {
                $normalizedDocCode = preg_replace('/[^a-z0-9]/', '', strtolower($r->Document_Code));
                return $normalizedDocCode === $normalizedUserCode;
            });

            if ($matchedRecords->isEmpty()) {
                return response()->view('errors.document_error', [
                    'message' => "Không tìm thấy thông tin tài liệu tương ứng với mã \"{$decodedCode}\" trong cơ sở dữ liệu."
                ], 404);
            }

            // Chọn bản ghi mới nhất (có ID lớn nhất)
            $bestRecord = $matchedRecords->sortByDesc('id')->first();
            $id = $bestRecord->id;
            $fileNameInDb = $bestRecord->FileimageFileName;
        } catch (\Exception $e) {
            Log::error("Lỗi khi truy vấn thông tin tài liệu từ DB Doc: " . $e->getMessage());
            return response()->view('errors.document_error', [
                'message' => 'Có lỗi xảy ra khi truy vấn cơ sở dữ liệu tài liệu: ' . $e->getMessage()
            ], 500);
        }

        // 2. Tìm kiếm file PDF trên đĩa mạng bắt đầu bằng ID
        $foundPath = null;
        $foundName = '';

        // Ưu tiên 1: Thử các đường dẫn trực tiếp (Cực nhanh - ~5-30ms)
        $directPaths = [
            $basePath . '/' . $id . '-' . $fileNameInDb,
            $basePath . '/' . $id . ' ' . $fileNameInDb,
        ];
        foreach ($directPaths as $path) {
            if (file_exists($path)) {
                $foundPath = $path;
                $foundName = basename($path);
                break;
            }
        }

        // Ưu tiên 2: Nếu không thấy, glob không đệ quy ở thư mục gốc (Nhanh - ~50ms)
        if (!$foundPath) {
            $patterns = [
                $basePath . '/' . $id . '-*.pdf',
                $basePath . '/' . $id . ' *.pdf',
            ];
            foreach ($patterns as $pattern) {
                $results = glob($pattern);
                if (!empty($results)) {
                    $foundPath = $results[0];
                    $foundName = basename($foundPath);
                    break;
                }
            }
        }

        // Ưu tiên 3: Nếu vẫn không thấy, glob ở các thư mục con cấp 1 (Khoảng 200-500ms)
        if (!$foundPath) {
            $patterns = [
                $basePath . '/*/' . $id . '-*.pdf',
                $basePath . '/*/' . $id . ' *.pdf',
            ];
            foreach ($patterns as $pattern) {
                $results = glob($pattern);
                if (!empty($results)) {
                    $foundPath = $results[0];
                    $foundName = basename($foundPath);
                    break;
                }
            }
        }

        // Ưu tiên 4: Fallback cuối cùng nếu vẫn không thấy - quét đệ quy sâu (Có thể chậm)
        if (!$foundPath) {
            try {
                $dirIterator = new \RecursiveDirectoryIterator($basePath, \RecursiveDirectoryIterator::SKIP_DOTS);
                $iterator = new \RecursiveIteratorIterator($dirIterator, \RecursiveDirectoryIterator::SELF_FIRST);

                foreach ($iterator as $fileInfo) {
                    if ($fileInfo->isFile() && strtolower($fileInfo->getExtension()) === 'pdf') {
                        $basename = $fileInfo->getBasename();
                        if (strpos($basename, (string)$id) === 0) {
                            $foundPath = $fileInfo->getRealPath();
                            $foundName = $basename;
                            break;
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::error("Lỗi khi quét đệ quy thư mục mạng tìm ID {$id}: " . $e->getMessage());
            }
        }

        // Trả kết quả file PDF
        if ($foundPath && file_exists($foundPath)) {
            return response()->file($foundPath, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $foundName . '"'
            ]);
        }

        return response()->view('errors.document_error', [
            'message' => "Không tìm thấy tài liệu PDF thực tế nào tương ứng với ID {$id} (mã \"{$decodedCode}\") trên máy chủ tài liệu mạng."
        ], 404);
    }

    /**
     * Kiểm tra ngầm sự tồn tại của file PDF tài liệu trên máy chủ đĩa mạng mà không trả về file.
     */
    public function checkDocumentExists(Request $request, $code)
    {
        $basePath = "\\\\10.71.1.57\\inetpub\\wwwroot\\ATLDOCPRO500_PQ\\DOCPROVIEWER500\\STELLA\\Documents";

        if (!is_dir($basePath)) {
            return response()->json([
                'exists' => false,
                'message' => 'Không thể kết nối tới máy chủ lưu trữ tài liệu (thư mục chia sẻ mạng không khả dụng).'
            ], 500);
        }

        $decodedCode = urldecode($code);
        $normalizedUserCode = preg_replace('/[^a-z0-9]/', '', strtolower($decodedCode));
        if (empty($normalizedUserCode)) {
            return response()->json([
                'exists' => false,
                'message' => 'Mã tài liệu kiểm tra không hợp lệ.'
            ], 400);
        }

        $cleanedCode = trim($decodedCode);
        $cleanedCode = preg_replace('/^[^a-zA-Z0-9]+|[^a-zA-Z0-9]+$/', '', $cleanedCode);

        try {
            $likePattern = '%' . str_replace(['-', '_', ' '], '%', $cleanedCode) . '%';
            $records = DB::connection('Doc')
                ->table('Document_records')
                ->where('Document_Code', 'like', $likePattern)
                ->get();

            $matchedRecords = $records->filter(function ($r) use ($normalizedUserCode) {
                return preg_replace('/[^a-z0-9]/', '', strtolower($r->Document_Code)) === $normalizedUserCode;
            });

            if ($matchedRecords->isEmpty()) {
                return response()->json([
                    'exists' => false,
                    'message' => "Không tìm thấy thông tin mã tài liệu \"{$decodedCode}\" trong cơ sở dữ liệu."
                ]);
            }

            $bestRecord = $matchedRecords->sortByDesc('id')->first();
            $id = $bestRecord->id;
            $fileNameInDb = $bestRecord->FileimageFileName;
        } catch (\Exception $e) {
            return response()->json([
                'exists' => false,
                'message' => 'Lỗi truy vấn cơ sở dữ liệu: ' . $e->getMessage()
            ], 500);
        }

        $foundPath = null;
        $directPaths = [
            $basePath . '/' . $id . '-' . $fileNameInDb,
            $basePath . '/' . $id . ' ' . $fileNameInDb,
        ];
        foreach ($directPaths as $path) {
            if (file_exists($path)) {
                $foundPath = $path;
                break;
            }
        }

        if (!$foundPath) {
            $patterns = [
                $basePath . '/' . $id . '-*.pdf',
                $basePath . '/' . $id . ' *.pdf',
            ];
            foreach ($patterns as $pattern) {
                $results = glob($pattern);
                if (!empty($results)) {
                    $foundPath = $results[0];
                    break;
                }
            }
        }

        if (!$foundPath) {
            $patterns = [
                $basePath . '/*/' . $id . '-*.pdf',
                $basePath . '/*/' . $id . ' *.pdf',
            ];
            foreach ($patterns as $pattern) {
                $results = glob($pattern);
                if (!empty($results)) {
                    $foundPath = $results[0];
                    break;
                }
            }
        }

        if (!$foundPath) {
            try {
                $dirIterator = new \RecursiveDirectoryIterator($basePath, \RecursiveDirectoryIterator::SKIP_DOTS);
                $iterator = new \RecursiveIteratorIterator($dirIterator, \RecursiveDirectoryIterator::SELF_FIRST);

                foreach ($iterator as $fileInfo) {
                    if ($fileInfo->isFile() && strtolower($fileInfo->getExtension()) === 'pdf') {
                        if (strpos($fileInfo->getBasename(), (string)$id) === 0) {
                            $foundPath = $fileInfo->getRealPath();
                            break;
                        }
                    }
                }
            } catch (\Exception $e) {
                // Ignore errors
            }
        }

        if ($foundPath && file_exists($foundPath)) {
            return response()->json([
                'exists' => true,
                'id' => $id,
                'fileName' => basename($foundPath),
                'version' => $bestRecord->Version
            ]);
        }

        return response()->json([
            'exists' => false,
            'message' => "Tài liệu khớp với ID {$id} (Version {$bestRecord->Version}) trong DB, nhưng không tìm thấy file PDF thực tế trên máy chủ đĩa mạng."
        ]);
    }

    public function getLogbookLabel(Request $request)
    {
        $type = $request->query('type'); // 'room' or 'equipment'
        $id = $request->query('id');

        $query = DB::table('room_logbooks')->orderBy('id', 'desc');

        if ($type === 'room') {
            $query->where('room_id', $id)->whereNull('equipment_id');
            // get room info
            $entity = DB::connection('pms')->table('room')->where('id', $id)->first();
        } else {
            $query->where('equipment_id', $id);
            // get equipment info
            $entity = DB::table('instrument')->where('id', $id)->first();
        }

        if (!$entity) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy thực thể']);
        }

        $logbook = $query->first();

        // format data for the UI
        $data = [
            'entity_name' => $entity->name,
            'entity_code' => $entity->code,
            'current_status' => $logbook ? $logbook->current_status : 'ready',
            'stage' => $logbook ? $logbook->stage : '',
            'lot_number' => $logbook ? $logbook->lot_number : '',
            'product_name' => $logbook ? $logbook->product_name : '',
            'batch_number' => $logbook ? $logbook->batch_number : '',
            'clean_level' => $logbook ? $logbook->clean_level : '',
            'clean_expiry_date' => $logbook && $logbook->clean_expiry_date ? date('d/m/Y H:i', strtotime($logbook->clean_expiry_date)) : '',
            'to_be_cleaned_before' => $logbook && $logbook->to_be_cleaned_before ? date('d/m/Y H:i', strtotime($logbook->to_be_cleaned_before)) : '',
            'end_time' => $logbook && $logbook->end_time ? date('d/m/Y H:i', strtotime($logbook->end_time)) : '',
            'next_product_name' => $logbook ? $logbook->next_product_name : '',
            'next_batch_number' => $logbook ? $logbook->next_batch_number : '',
        ];

        // get user names
        if ($logbook) {
            $created_by = DB::table('user_management')->where('id', $logbook->created_by)->value('fullName');
            $checked_by = $logbook->checked_by ? DB::table('user_management')->where('id', $logbook->checked_by)->value('fullName') : '';
            $attached_by = $logbook->attached_by ? DB::table('user_management')->where('id', $logbook->attached_by)->value('fullName') : '';
            
            $data['done_by'] = $created_by;
            $data['checked_by'] = $checked_by;
            $data['attached_by'] = $attached_by;
        } else {
            $data['done_by'] = '';
            $data['checked_by'] = '';
            $data['attached_by'] = '';
        }

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }
}
