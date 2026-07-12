<?php

namespace App\Http\Controllers\Pages\Ebmr\Records;

use App\Http\Controllers\Controller;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Services\RunDataEncryptionService;
use Carbon\Carbon;
use App\Models\ClearanceRoomProcessList;
use App\Models\ClearanceRoomProcess;
use App\Models\ClearanceRoomCampaign;
use App\Models\ClearanceRoomCampaignStep;
use App\Models\ClearanceEquipProcessList;
use App\Models\ClearanceEquipProcess;
use App\Models\ClearanceEquipCampaign;
use App\Models\ClearanceEquipCampaignStep;

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
            ->select('ebmr_records.*', 'user_management.fullName as issuer_name', 'ebmr_templates.type', 'ebmr_templates.caterogy_id', 'ebmr_templates.doc_properties');

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
                    ->select('finished_product_category.finished_product_code', 'finished_product_category.deparment_code', 'product_name.name')
                    ->first();
                $r->template_name = $cat->name ?? 'N/A';
                $r->document_code = $cat->finished_product_code ?? 'N/A';
                $r->workshop = $cat->deparment_code ?? null;
            } else {
                $cat = DB::table('intermediate_category')
                    ->leftJoin('product_name', 'intermediate_category.product_name_id', '=', 'product_name.id')
                    ->where('intermediate_category.id', $r->caterogy_id)
                    ->select('intermediate_category.intermediate_code', 'intermediate_category.deparment_code', 'product_name.name')
                    ->first();
                $r->template_name = $cat->name ?? 'N/A';
                $r->document_code = $cat->intermediate_code ?? 'N/A';
                $r->workshop = $cat->deparment_code ?? null;
            }
            // GF/MF không gắn phân xưởng
            $r->workshop = $r->workshop ?? null;

            // --- Fetch Stages (Sections) ---
            // Gộp các công đoạn liên tiếp ĐÃ NỐI TRANG (noPageBreak trong properties — xem
            // toggleSectionPageBreakV2 trong main.js) thành 1 đơn vị phân phối: về mặt vật lý
            // chúng nằm chung 1 trang in nên không thể giao cho 2 phòng khác nhau. member_ids
            // giữ lại toàn bộ section_id gốc để khi lưu vẫn ghi nhận riêng cho từng công đoạn.
            //
            // Số tiêu đề (display_label, VD "4.", "4.1.") được tính lại y hệt updateHeadingNumbersV2()
            // trong main.js (nhóm theo category::stageCode rút từ section_id — xem sectionTrackInfoV2)
            // để hiển thị trong modal Phân Phối Công Đoạn giống hệt số trên hồ sơ gốc.
            //
            // Trường hợp riêng: tiêu đề GỐC (track 1) của 1 nhóm NHÁNH PHÒNG (xem
            // splitSectionIntoRoomTrackV2) bật noPageBreak nghĩa là "kéo NHÁNH ĐẦU TIÊN lên cùng
            // trang với tiêu đề" (xem comment tại main.js ~716), không phải tự nối lên trang TRƯỚC
            // nó. Tiêu đề gốc dạng này thường không có nội dung riêng nên không tạo dòng phân phối
            // rỗng: label của nó được gộp vào nhánh kế tiếp. Các nhánh SAU nhánh đầu tiên (track 2,
            // 3, ...) vẫn đứng riêng — đúng mục đích "soạn và phân phối ĐỘC LẬP" của tính năng
            // tách nhánh phòng.
            $headingNumberingOn = true;
            if (!empty($r->doc_properties)) {
                $docProps = json_decode($r->doc_properties, true);
                $hn = $docProps['__headingNumbering'] ?? null;
                if ($hn === false || $hn === 'false' || $hn === 0 || $hn === '0') {
                    $headingNumberingOn = false;
                }
            }

            $sectionBlocks = DB::table('ebmr_template_blocks')
                ->where('template_id', $r->template_id)
                ->where('type', 'section')
                ->orderBy('order')
                ->get();

            $sectionTrackKey = function ($sectionId) {
                $parts = explode('_', (string) $sectionId);
                if (count($parts) >= 3 && is_numeric($parts[1])) {
                    return [
                        'key' => $parts[0] . '::' . implode('_', array_slice($parts, 2)),
                        'track' => (int) $parts[1],
                    ];
                }
                return [
                    'key' => implode('_', array_slice($parts, 0, -1)) . '::' . (end($parts) ?: ''),
                    'track' => 1,
                ];
            };

            $trackCounts = [];
            foreach ($sectionBlocks as $b) {
                $ti = $sectionTrackKey($b->section_id);
                $trackCounts[$ti['key']] = max($trackCounts[$ti['key']] ?? 1, $ti['track']);
            }

            $sectionGroups = [];
            $groupSecN = [];
            $secN = 0;
            $pendingLabel = null;
            $pendingId = null;
            $pendingNumber = null;
            foreach ($sectionBlocks as $b) {
                $prop = json_decode($b->properties);
                $label = $prop->label ?? 'N/A';
                $ti = $sectionTrackKey($b->section_id);
                $key = $ti['key'];
                $track = $ti['track'];

                $numStr = null;
                if ($headingNumberingOn && empty($prop->locked)) {
                    if (!isset($groupSecN[$key])) {
                        $secN++;
                        $groupSecN[$key] = $secN;
                    }
                    $gSecN = $groupSecN[$key];
                    $numStr = $track >= 2 ? ($gSecN . '.' . ($track - 1)) : (string) $gSecN;
                }
                $displayLabel = $numStr ? ($numStr . '. ' . $label) : $label;

                $isRootWithBranches = $track === 1 && ($trackCounts[$key] ?? 1) >= 2;
                if ($isRootWithBranches && !empty($prop->noPageBreak)) {
                    $pendingLabel = $label;
                    $pendingId = $b->section_id;
                    $pendingNumber = $numStr;
                    continue;
                }

                if ($pendingLabel !== null) {
                    $sectionGroups[] = [
                        'id' => $pendingId,
                        'label' => $pendingLabel . ' + ' . $label,
                        'display_label' => ($pendingNumber ? $pendingNumber . '. ' : '') . $pendingLabel . ' + ' . $displayLabel,
                        'member_ids' => [$pendingId, $b->section_id],
                    ];
                    $pendingLabel = null;
                    $pendingId = null;
                    $pendingNumber = null;
                    continue;
                }

                if (!empty($prop->noPageBreak) && !empty($sectionGroups)) {
                    $lastIdx = count($sectionGroups) - 1;
                    $sectionGroups[$lastIdx]['member_ids'][] = $b->section_id;
                    $sectionGroups[$lastIdx]['label'] .= ' + ' . $label;
                    $sectionGroups[$lastIdx]['display_label'] .= ' + ' . $displayLabel;
                } else {
                    $sectionGroups[] = [
                        'id' => $b->section_id,
                        'label' => $label,
                        'display_label' => $displayLabel,
                        'member_ids' => [$b->section_id],
                    ];
                }
            }
            // Tiêu đề gốc đang chờ gộp mà không có nhánh nào theo sau (dữ liệu bất thường) —
            // vẫn hiển thị thay vì mất hẳn khỏi danh sách phân phối.
            if ($pendingLabel !== null) {
                $sectionGroups[] = [
                    'id' => $pendingId,
                    'label' => $pendingLabel,
                    'display_label' => ($pendingNumber ? $pendingNumber . '. ' : '') . $pendingLabel,
                    'member_ids' => [$pendingId],
                ];
            }
            $r->sections = collect($sectionGroups);

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
                'ebmr_record_distributions.user_ids as distribution_user_ids',
                'ebmr_record_distributions.started_at as production_started_at',
                'ebmr_record_distributions.clearance_completed_at',
                'ebmr_record_distributions.production_ended_at'
            )
            ->whereNotIn('ebmr_records.status', ['completed', 'reviewed'])
            ->get();

        $currentUserId = (int) (session('user')['userId'] ?? 0);

        // Memo kết quả kiểm tra vệ sinh theo phòng (getRoomCleaningReadiness quét logbook
        // phòng + toàn bộ thiết bị) — nhiều công đoạn cùng phòng chỉ cần check 1 lần.
        $roomReadinessCache = [];

        // Memo danh sách quy trình dọn quang còn thiếu theo phòng — dùng để chặn ngay khi
        // bấm "Bắt đầu sản xuất" nếu phòng/thiết bị chưa được thiết kế quy trình.
        $clearanceProcessCache = [];

        // Populate product name + phân phối/ghi chép cho từng hồ sơ đang hoạt động
        foreach ($activeRecords as $r) {
            $r->product_name = $this->resolveProductName($r->type, $r->caterogy_id);

            // Luồng 3 trạng thái cho từng công đoạn đã phân phối:
            // (1) "Bắt đầu sản xuất" (can_start): đã phân phối + user được phân công + phòng và
            //     toàn bộ thiết bị đã "Đã vệ sinh" (KHÔNG cần dọn quang xong) + CHƯA bắt đầu.
            // (2) "Tiếp tục dọn quang" (can_resume_clearance): đã bắt đầu (started_at) nhưng
            //     dọn quang phòng + thiết bị CHƯA hoàn tất (clearance_completed_at rỗng) — dọn
            //     quang là bước bắt buộc PHÁT SINH SAU khi bấm Bắt đầu sản xuất, không phải điều
            //     kiện tiên quyết.
            // (3) "Ghi chép dữ liệu" (can_write): đã bắt đầu VÀ đã hoàn tất dọn quang.
            // Chưa đủ điều kiện ở bước (1) thì chỉ được "Xem hồ sơ" (read-only), kèm danh sách
            // lý do cụ thể (cleaning_missing) để hiển thị thẳng lên card, không cần hover.
            $r->is_distributed = !is_null($r->distribution_id);
            $r->distribution_user_id_list = $r->distribution_user_ids ? (json_decode($r->distribution_user_ids, true) ?: []) : [];
            $isAssigned = in_array($currentUserId, array_map('intval', $r->distribution_user_id_list), true);
            $r->is_assigned = $isAssigned;
            if ($r->room_id && !array_key_exists($r->room_id, $roomReadinessCache)) {
                $roomReadinessCache[$r->room_id] = $this->getRoomCleaningReadiness($r->room_id);
            }
            $readiness = $r->room_id ? $roomReadinessCache[$r->room_id] : ['ready' => false, 'missing' => ['Chưa gán phòng']];
            $r->room_cleaning_ready = $readiness['ready'];
            $r->cleaning_missing = $readiness['missing'];
            if ($r->room_id && !array_key_exists($r->room_id, $clearanceProcessCache)) {
                $clearanceProcessCache[$r->room_id] = $this->getMissingClearanceProcesses($r->room_id);
            }
            $r->clearance_process_missing = $r->room_id ? $clearanceProcessCache[$r->room_id] : ['Chưa gán phòng'];
            $r->production_started = !is_null($r->production_started_at);
            $r->clearance_completed = !is_null($r->clearance_completed_at);
            $r->production_ended = !is_null($r->production_ended_at);
            $r->can_write = $r->is_distributed && $isAssigned && $r->production_started && $r->clearance_completed;
            $r->can_start = $r->is_distributed && $isAssigned && !$r->production_started && $r->room_cleaning_ready;
            $r->can_resume_clearance = $r->is_distributed && $isAssigned && $r->production_started && !$r->clearance_completed;
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
            $reading = \App\Services\ProductionEnvironmentService::simulateReading($room->id, $time);

            $telemetries[$room->id] = [
                'temperature' => number_format($reading['temperature'], 1),
                'humidity' => number_format($reading['humidity'], 0),
                'pressure' => '+' . number_format($reading['pressure'], 0),
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

        // Các con dấu đã chọn khi ban hành lô (nếu có) — đóng cạnh nhau lên góc trên
        // bên phải mỗi phân đoạn, giữ đúng thứ tự người dùng chọn lúc ban hành
        $sealIds = json_decode($record->seal_ids ?? '[]', true) ?: [];
        $recordSeals = $sealIds
            ? DB::table('seals')->whereIn('id', $sealIds)->get()
                ->sortBy(fn($s) => array_search($s->id, $sealIds))->values()
            : collect();

        $template = DB::table('ebmr_templates')
            ->leftJoin('user_management', 'ebmr_templates.owner_id', '=', 'user_management.id')
            ->leftJoin('designations', 'user_management.designation_id', '=', 'designations.id')
            ->where('ebmr_templates.id', $record->template_id)
            ->select('ebmr_templates.*', 'user_management.fullName as owner_name', 'user_management.signature_image as owner_signature', 'designations.name as owner_designation')
            ->first();
        if (!$template) return redirect()->back()->with('error', 'Mẫu hồ sơ không tồn tại.');

        // Chỉ lấy vòng trình ký mới nhất (các dòng cùng created_at với dòng mới nhất —
        // cùng cách nhóm với getTemplateWorkflow), bỏ các vòng cũ đã bị từ chối.
        $latestWfBatch = DB::table('ebmr_template_workflows')->where('template_id', $template->id)->orderBy('id', 'desc')->value('created_at');
        $template->workflows = DB::table('ebmr_template_workflows')
            ->leftJoin('user_management', 'ebmr_template_workflows.user_id', '=', 'user_management.id')
            ->leftJoin('designations', 'user_management.designation_id', '=', 'designations.id')
            ->where('template_id', $template->id)
            ->when($latestWfBatch, fn($q) => $q->where('ebmr_template_workflows.created_at', $latestWfBatch))
            ->orderBy('step_order')
            ->select('ebmr_template_workflows.*', 'user_management.fullName', 'user_management.groupName as title', 'user_management.deparment as department_name', 'user_management.signature_image as signature_image', 'designations.name as designation_name')
            ->get();

        // Lịch sử thay đổi ấn bản (dùng cho khối ảo "LỊCH SỬ THAY ĐỔI ẤN BẢN")
        // Chỉ hiện các ấn bản <= ấn bản đang xem (vd: xem ấn bản 02 thì hiện lý do của 00, 01, 02)
        $template->version_history = DB::table('ebmr_templates')
            ->where('caterogy_id', $template->caterogy_id)
            ->where('type', $template->type)
            ->where('version', '<=', $template->version)
            ->orderBy('version', 'asc')
            ->select('version', 'change_reason', 'effective_date')
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
            $template->stage_name = $cat->stage_name ?? '';
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

            // Các trường phục vụ khối ảo CÔNG THỨC PHA CHẾ / MÔ TẢ SẢN PHẨM của giao diện V2
            // (port từ EbmrDesignerController::show — thiếu thì các khối ảo này render rỗng)
            $template->batch_qty = ($cat->batch_qty ?? '') . ' ' . ($cat->unit_batch_qty ?? '');
            $template->raw_batch_size = (float)($cat->batch_size ?? 0);
            $template->unit_batch_size = $cat->unit_batch_size ?? '';

            $formulas = DB::table('formula_preparation')
                ->where('ebmr_templates_id', $template->id)
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

        $fields = [];
        $fieldsConfig = new \stdClass();

        $blocks = DB::table('ebmr_template_blocks')->where('template_id', $template->id)->orderBy('order')->get();

        // Fetch all testing criteria and properties for dynamic reference replacement (main + linked templates)
        $templateIds = [$template->id];
        foreach ($blocks as $block) {
            $f = json_decode($block->properties, true);
            if (isset($f['type']) && $f['type'] === 'linked-template') {
                $ltId = \App\Services\LinkedGfResolver::resolveTemplateId($f, (int) $id, $block->id);
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
                $hostBlockId = $block->id;
                $linkedTemplateId = \App\Services\LinkedGfResolver::resolveTemplateId($f, (int) $id, $hostBlockId);
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
                    // Namespace theo host-block để cùng 1 GF chèn lặp lại nhiều vị trí
                    // trong 1 BMR không đụng id field (block_uuid) lẫn nhau.
                    $linkedConfig = \App\Services\LinkedGfResolver::namespaceLinkedFieldsConfig((array) $linkedConfig, $hostBlockId);
                    $fieldsConfig = array_merge((array)$fieldsConfig, (array)$linkedConfig);
                    foreach ($linkedBlocks as $lb) {
                        $linkedF = json_decode($lb->properties, true);
                        $this->injectContent($linkedF, $lb, $lContentBlocks->get($lb->id), $testingCriteria, $properties);
                        $linkedF['is_linked'] = true; // Mark as linked if needed by frontend
                        $linkedF = \App\Services\LinkedGfResolver::namespaceLinkedField($linkedF, $hostBlockId);
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
        // $sectionId có thể là 1 id đơn hoặc danh sách "id1,id2" (nhiều công đoạn ĐÃ NỐI TRANG
        // với nhau — xem modal Phân Phối Công Đoạn: các công đoạn này được gộp thành 1 đơn vị
        // phân phối nên khi xem trước cũng cần hiển thị gộp nội dung của tất cả).
        $activeSectionLabel = null;
        $activeSectionNumber = null;
        $sectionIds = $sectionId ? array_values(array_filter(array_map('trim', explode(',', (string) $sectionId)))) : [];
        if ($sectionId) {
            // Khi lọc chỉ còn 1 (hoặc vài) công đoạn, JS đánh số tiêu đề (updateHeadingNumbersV2
            // trong main.js) sẽ đếm lại từ đầu trên tập DOM đang hiển thị -> luôn ra "1." dù công
            // đoạn này thực sự là số mấy trong toàn hồ sơ gốc. Tính trước số thứ tự THẬT ở
            // đây (lặp qua $allFields đầy đủ, gom nhóm y hệt sectionTrackInfoV2() phía JS:
            // key = category::stageCode rút từ section_id) rồi truyền xuống để JS bù trừ.
            $groupSecN = [];
            $secN = 0;
            foreach ($allFields as $f) {
                if (($f['type'] ?? null) !== 'section' || !empty($f['isVirtual']) || !empty($f['locked'])) continue;
                $parts = explode('_', (string) ($f['section_id'] ?? ''));
                if (count($parts) >= 3 && is_numeric($parts[1])) {
                    $category = $parts[0];
                    $stageCode = implode('_', array_slice($parts, 2));
                } else {
                    $category = implode('_', array_slice($parts, 0, -1));
                    $stageCode = end($parts) ?: '';
                }
                $key = $category . '::' . $stageCode;
                if (!isset($groupSecN[$key])) {
                    $secN++;
                    $groupSecN[$key] = $secN;
                }
                if ($activeSectionNumber === null && in_array((string) ($f['section_id'] ?? ''), $sectionIds, true)) {
                    $activeSectionNumber = $groupSecN[$key];
                }
            }

            $blocksQuery = DB::table('ebmr_template_blocks')
                ->where('template_id', $template->id)
                ->where(function ($q) use ($sectionIds, $template) {
                    $q->whereIn('section_id', $sectionIds)
                        ->orWhere('section_id', (string)$template->caterogy_id);
                })
                ->orderBy('order')
                ->get();

            $bqIds = $blocksQuery->pluck('id')->toArray();
            $bqContentBlocks = DB::table('ebmr_content_blocks')->whereIn('ebmr_template_blocks_id', $bqIds)->get()->groupBy('ebmr_template_blocks_id');

            $activeSectionLabelParts = [];
            foreach ($blocksQuery as $block) {
                $f = json_decode($block->properties, true);
                $this->injectContent($f, $block, $bqContentBlocks->get($block->id));
                if (isset($f['type']) && $f['type'] === 'section' && in_array((string) $block->section_id, $sectionIds, true)) {
                    $activeSectionLabelParts[] = $f['label'] ?? 'Phân đoạn';
                }

                if (isset($f['type']) && $f['type'] === 'linked-template') {
                    $hostBlockId = $block->id;
                    $linkedTemplateId = \App\Services\LinkedGfResolver::resolveTemplateId($f, (int) $id, $hostBlockId);
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
                            $linkedConfig = \App\Services\LinkedGfResolver::namespaceLinkedFieldsConfig((array) $linkedConfig, $hostBlockId);
                            $fieldsConfig = array_merge((array)$fieldsConfig, (array)$linkedConfig);
                        }
                        foreach ($linkedBlocks as $lb) {
                            $linkedF = json_decode($lb->properties, true);
                            $this->injectContent($linkedF, $lb, $lContentBlocks->get($lb->id));
                            $linkedF['is_linked'] = true;
                            $linkedF = \App\Services\LinkedGfResolver::namespaceLinkedField($linkedF, $hostBlockId);
                            $fields[] = $linkedF;
                        }
                    }
                } else {
                    $fields[] = $f;
                }
            }
            $activeSectionLabel = $activeSectionLabelParts ? implode(' + ', $activeSectionLabelParts) : null;
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
                'by' => RunDataEncryptionService::decrypt($rd->updated_by),
                'at' => $rd->updated_at ? \Carbon\Carbon::parse($rd->updated_at)->format('d/m/Y H:i') : null,
                'history_count' => $historyCounts[$rd->id] ?? 0
            ];
        }

        $template->schema = (object)['fields' => $fields, 'fieldsConfig' => $fieldsConfig];

        // Mở từ tab "Nhận Ban Hành" (?from=issuance): luôn chỉ xem, ẩn toàn bộ
        // giao diện bình luận + nút Lưu nháp / Hoàn Thành Nhập Liệu.
        $isIssuanceView = $request->query('from') === 'issuance';
        $isReadOnly = $isIssuanceView ? true : $this->computeIsReadOnly($request, $record);

        // Dữ liệu bổ sung cho view designer_v2 (dùng chung blade với Designer V2)
        $comments = DB::table('ebmr_template_comments')
            ->leftJoin('user_management', 'ebmr_template_comments.user_id', '=', 'user_management.id')
            ->where('template_id', $template->id)
            ->select('ebmr_template_comments.*', 'user_management.fullName as user_name')
            ->orderBy('created_at', 'asc')
            ->get();
        $importantVars = DB::table('important_var')->get();

        // Phòng đang thực thi công đoạn này — dùng cho nút "Quay lại" ở thanh công cụ chế độ
        // thực thi (không có leftNAV để quay về Phòng Sản Xuất): ưu tiên ?dist= của link vừa
        // bấm tới, nếu không có thì tìm theo record + section (link "Xem hồ sơ" không kèm dist).
        $backDistId = $request->query('dist');
        $backDist = $backDistId
            ? DB::table('ebmr_record_distributions')->where('id', $backDistId)->where('record_id', $record->id)->first()
            : ($sectionId ? DB::table('ebmr_record_distributions')->where('record_id', $record->id)->where('section_id', $sectionId)->first() : null);
        $backRoomId = $backDist->room_id ?? null;

        // Người bấm "Bắt đầu sản xuất" cho phòng/công đoạn hiện tại — dùng cho modal
        // Thông tin lô trên toolbar (designer_v2.blade.php).
        $backDistExecutorName = ($backDist->started_by ?? null)
            ? DB::table('user_management')->where('id', $backDist->started_by)->value('fullName')
            : null;

        // Cấu trúc phát sinh (dòng thêm Cấp 2...) đã lưu riêng cho LÔ này
        $recordStructures = [];
        foreach (DB::table('ebmr_record_structures')->where('record_id', $id)->get() as $rs) {
            $recordStructures[$rs->block_uuid] = [
                'kind' => $rs->kind,
                'payload' => json_decode($rs->payload, true) ?: [],
            ];
        }

        // Tài liệu PDF đã đính kèm cho từng phân đoạn của LÔ này (record_id + section_id,
        // xem ebmr_record_section_attachments) — gom nhóm theo section_id để JS hiển thị
        // badge số lượng + danh sách ngay trên từng phân đoạn (xem attachments.js).
        $sectionAttachments = DB::table('ebmr_record_section_attachments')
            ->where('record_id', $id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('section_id')
            ->map(fn($rows) => $rows->map(fn($r) => $this->formatSectionAttachment($r))->values());

        return view('pages.ebmr.designer_v2', [
            'record' => $record,
            'recordSeals' => $recordSeals,
            'template' => $template,
            'executionValues' => $executionValues,
            'recordStructures' => (object)$recordStructures,
            'sectionAttachments' => (object)$sectionAttachments->toArray(),
            'isExecutionMode' => true,
            'isReadOnly' => $isReadOnly,
            'isIssuanceView' => $isIssuanceView,
            'comments' => $isIssuanceView ? collect() : $comments,
            'importantVars' => $importantVars,
            'activeSectionId' => $sectionId,
            'activeSectionLabel' => $activeSectionLabel,
            'activeSectionNumber' => $activeSectionNumber,
            'isAdmin' => (session('user')['userGroup'] ?? '') === 'Admin',
            'backRoomId' => $backRoomId,
            'backDist' => $backDist,
            'backDistExecutorName' => $backDistExecutorName,
            // Đích "Quay lại ghi chép" khi đang ở chế độ Xem tất cả công đoạn — được nút "Xem
            // tất cả công đoạn" nhúng vào link lúc điều hướng (xem designer_v2.blade.php),
            // không tự tra cứu lại ở đây để tránh chọn nhầm công đoạn nếu 1 user được phân
            // công nhiều công đoạn cùng lúc. Quyền ghi thật sự vẫn được computeIsReadOnly()
            // thẩm định lại khi bấm quay lại (?section=&dist=), nên không phát sinh rủi ro.
            'returnSectionId' => $request->query('returnSection'),
            'returnDistId' => $request->query('returnDist'),
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

        // Chỉ được ghi khi phiên sản xuất ĐÃ bắt đầu (started_at) VÀ dọn quang phòng +
        // toàn bộ thiết bị đã hoàn tất (clearance_completed_at) — dọn quang là bước bắt
        // buộc phát sinh sau "Bắt đầu sản xuất", chưa xong thì vẫn chỉ được xem.
        return !($isAssigned && !is_null($dist->started_at) && !is_null($dist->clearance_completed_at));
    }

    /**
     * Kiểm tra phòng (và toàn bộ thiết bị được khai báo trong phòng) đã đạt cả
     * 2 điều kiện: (a) nhãn vệ sinh gần nhất còn hiệu lực (current_status =
     * 'cleaned', chưa quá clean_expiry_date), (b) đã có 1 chiến dịch dọn quang
     * (clearance) hoàn tất cho phòng/thiết bị đó.
     */
    /**
     * Điều kiện SẢN XUẤT của 1 phòng chỉ xét vệ sinh (phòng + từng thiết bị đã "Đã vệ
     * sinh", nhãn chưa hết hạn) — dọn quang KHÔNG phải điều kiện tiên quyết, mà là bước
     * bắt buộc thực hiện SAU KHI đã bấm "Bắt đầu sản xuất" (xem startProduction()).
     * Trả về danh sách lý do còn thiếu bằng tiếng Việt để hiển thị trực tiếp lên card
     * phòng, không chỉ 1 boolean — người dùng cần biết chính xác đang thiếu gì.
     */
    private function getRoomCleaningReadiness($roomId): array
    {
        $missing = [];
        if (!$roomId) return ['ready' => false, 'missing' => ['Phòng không xác định']];

        $roomLog = DB::table('room_logbooks')
            ->where('room_id', $roomId)
            ->whereNull('equipment_id')
            ->orderByDesc('id')
            ->first();
        if (!$roomLog || $roomLog->current_status !== 'cleaned') {
            $missing[] = 'Phòng chưa vệ sinh';
        } elseif ($roomLog->clean_expiry_date && Carbon::parse($roomLog->clean_expiry_date)->isPast()) {
            $missing[] = 'Nhãn vệ sinh phòng đã hết hạn';
        }

        $equipRows = DB::table('equipment_in_room')
            ->join('instrument', 'equipment_in_room.equipment_id', '=', 'instrument.id')
            ->where('equipment_in_room.room_id', $roomId)
            ->select('instrument.id', 'instrument.code')
            ->get();
        foreach ($equipRows as $eq) {
            $eqLog = DB::table('room_logbooks')
                ->where('equipment_id', $eq->id)
                ->orderByDesc('id')
                ->first();
            if (!$eqLog || $eqLog->current_status !== 'cleaned') {
                $missing[] = "Thiết bị {$eq->code} chưa vệ sinh";
            } elseif ($eqLog->clean_expiry_date && Carbon::parse($eqLog->clean_expiry_date)->isPast()) {
                $missing[] = "Nhãn vệ sinh thiết bị {$eq->code} đã hết hạn";
            }
        }

        return ['ready' => empty($missing), 'missing' => $missing];
    }

    /**
     * Tên sản phẩm hiển thị theo loại tài liệu (GF/MF/BPR/BMR) — dùng chung cho
     * dashboard Sản Xuất và nhật ký phòng khi bắt đầu sản xuất.
     */
    private function resolveProductName($type, $caterogyId)
    {
        return \App\Services\EbmrProductResolver::resolveName($type, $caterogyId);
    }

    /**
     * Tìm quy trình dọn quang đang áp dụng (ưu tiên active → approved → submitted →
     * draft) cho 1 phòng hoặc 1 thiết bị — dùng chung logic ưu tiên với
     * ClearanceProcessController::openCampaignPage để 2 nơi luôn chọn ra cùng 1 bản.
     */
    private function findActiveClearanceProcessList(string $model, string $column, $id)
    {
        return $model::where($column, $id)
            ->where('clearance_type', 1)
            ->whereIn('status', ['active', 'approved', 'submitted', 'draft'])
            ->orderByRaw("FIELD(status, 'active', 'approved', 'submitted', 'draft')")
            ->orderBy('version', 'desc')
            ->first();
    }

    /**
     * Liệt kê các quy trình dọn quang còn THIẾU cho 1 phòng (phòng + từng thiết bị trong
     * phòng). Trả mảng lý do rỗng nghĩa là đủ quy trình để bắt đầu sản xuất. Dùng ở trang
     * Sản Xuất để chặn NGAY khi bấm "Bắt đầu sản xuất" (không cho vào dialog xác nhận nếu
     * thiếu) — cùng bộ điều kiện mà startProduction() kiểm tra lại phía server.
     */
    private function getMissingClearanceProcesses($roomId): array
    {
        $missing = [];

        if (!$this->findActiveClearanceProcessList(ClearanceRoomProcessList::class, 'room_id', $roomId)) {
            $missing[] = 'Phòng chưa có quy trình dọn quang';
        }

        $equipRows = DB::table('equipment_in_room')
            ->join('instrument', 'equipment_in_room.equipment_id', '=', 'instrument.id')
            ->where('equipment_in_room.room_id', $roomId)
            ->select('instrument.id', 'instrument.code')
            ->get();

        $missingEquip = [];
        foreach ($equipRows as $eq) {
            if (!$this->findActiveClearanceProcessList(ClearanceEquipProcessList::class, 'equipment_id', $eq->id)) {
                $missingEquip[] = $eq->code;
            }
        }
        if (!empty($missingEquip)) {
            $missing[] = 'Thiết bị chưa có quy trình dọn quang: ' . implode(', ', $missingEquip);
        }

        return $missing;
    }

    /**
     * Bắt đầu sản xuất 1 công đoạn đã phân phối. 2 nhánh:
     * (a) Đã từng bắt đầu nhưng dọn quang chưa xong → điều hướng thẳng tới campaign dọn
     *     quang phòng đang dở (idempotent, không tạo lại).
     * (b) Chưa từng bắt đầu → kiểm tra lại toàn bộ điều kiện phía server (user được
     *     phân công, hồ sơ chưa hoàn thành, phòng + thiết bị đã vệ sinh — KHÔNG cần dọn
     *     quang xong, đó là bước kế tiếp), chốt started_at/started_by, ghi nhật ký phòng
     *     'producing', rồi TỰ TẠO campaign dọn quang cho phòng + từng thiết bị trong
     *     phòng (gắn distribution_id) và điều hướng người dùng vào đó. Trang ghi chép hồ
     *     sơ chỉ mở khi dọn quang hoàn tất (xem ClearanceProcessController::completeCampaign).
     */
    public function startProduction(Request $request)
    {
        $validated = $request->validate([
            'distribution_id' => 'required|integer',
        ]);

        $dist = DB::table('ebmr_record_distributions')->where('id', $validated['distribution_id'])->first();
        if (!$dist) {
            return response()->json(['success' => false, 'message' => 'Phân phối không tồn tại hoặc đã bị thu hồi.']);
        }

        $record = DB::table('ebmr_records')->where('id', $dist->record_id)->first();
        if (!$record) {
            return response()->json(['success' => false, 'message' => 'Hồ sơ không tồn tại.']);
        }
        if (in_array($record->status, ['completed', 'reviewed'])) {
            return response()->json(['success' => false, 'message' => 'Hồ sơ đã hoàn thành, không thể bắt đầu sản xuất.']);
        }

        $currentUserId = (int) (session('user')['userId'] ?? 0);
        $userIds = array_map('intval', json_decode($dist->user_ids ?? '[]', true) ?: []);
        if (!in_array($currentUserId, $userIds, true)) {
            return response()->json(['success' => false, 'message' => 'Bạn không nằm trong danh sách được phân phối công đoạn này.']);
        }

        $executeUrl = route('pages.ebmr.execute', $dist->record_id)
            . '?section=' . urlencode($dist->section_id) . '&dist=' . $dist->id;

        if (!is_null($dist->started_at)) {
            if (!is_null($dist->clearance_completed_at)) {
                return response()->json(['success' => true, 'already_started' => true, 'redirect_url' => $executeUrl]);
            }

            // Đã bắt đầu nhưng dọn quang dở dang — quay lại đúng campaign phòng đã tạo.
            $roomCampaign = ClearanceRoomCampaign::where('distribution_id', $dist->id)->first();
            if ($roomCampaign) {
                $clearanceUrl = route('pages.manu_env.clearance_process.campaign.open', ['room_id' => $dist->room_id])
                    . '?campaign_id=' . $roomCampaign->id;
                return response()->json(['success' => true, 'needs_clearance' => true, 'redirect_url' => $clearanceUrl]);
            }
            // Trường hợp hiếm: đã start nhưng chưa có campaign (vd lỗi giữa chừng lần
            // trước) — rơi xuống bên dưới để tạo lại campaign, giữ nguyên started_at gốc.
        }

        $readiness = $this->getRoomCleaningReadiness($dist->room_id);
        if (!$readiness['ready']) {
            return response()->json(['success' => false, 'message' => 'Phòng chưa đủ điều kiện sản xuất: ' . implode('; ', $readiness['missing'])]);
        }

        // Xác nhận có quy trình dọn quang cho phòng + MỌI thiết bị trong phòng trước khi
        // cho bắt đầu — tránh tình huống đã "bắt đầu sản xuất" nhưng kẹt lại vì thiếu quy
        // trình, không có đường lùi để ghi chép.
        $roomProcessList = $this->findActiveClearanceProcessList(ClearanceRoomProcessList::class, 'room_id', $dist->room_id);
        if (!$roomProcessList) {
            return response()->json(['success' => false, 'message' => 'Phòng chưa có quy trình dọn quang. Vui lòng thiết kế quy trình tại Môi Trường > Dọn Quang Phòng trước khi sản xuất.']);
        }

        $equipRows = DB::table('equipment_in_room')
            ->join('instrument', 'equipment_in_room.equipment_id', '=', 'instrument.id')
            ->where('equipment_in_room.room_id', $dist->room_id)
            ->select('instrument.id', 'instrument.code')
            ->get();

        $equipProcessLists = [];
        $missingEquipProcess = [];
        foreach ($equipRows as $eq) {
            $epl = $this->findActiveClearanceProcessList(ClearanceEquipProcessList::class, 'equipment_id', $eq->id);
            if (!$epl) {
                $missingEquipProcess[] = $eq->code;
                continue;
            }
            $equipProcessLists[$eq->id] = $epl;
        }
        if (!empty($missingEquipProcess)) {
            return response()->json(['success' => false, 'message' => 'Thiết bị sau chưa có quy trình dọn quang: ' . implode(', ', $missingEquipProcess) . '. Vui lòng thiết kế quy trình tại Môi Trường > Dọn Quang Thiết Bị trước khi sản xuất.']);
        }

        $now = now();
        DB::beginTransaction();
        try {
            if (is_null($dist->started_at)) {
                DB::table('ebmr_record_distributions')->where('id', $dist->id)->update([
                    'started_at' => $now,
                    'started_by' => $currentUserId,
                    'updated_at' => $now,
                ]);

                // Nhật ký phòng chuyển 'producing' — trang Sản Xuất hiển thị nhãn "Đang sản xuất"
                $prevStatus = DB::table('room_logbooks')
                    ->where('room_id', $dist->room_id)
                    ->whereNull('equipment_id')
                    ->orderByDesc('id')
                    ->value('current_status') ?? 'cleaned';

                $template = DB::table('ebmr_templates')->where('id', $record->template_id)->first();
                $productName = $template ? $this->resolveProductName($template->type, $template->caterogy_id) : null;

                DB::table('room_logbooks')->insert([
                    'room_id' => $dist->room_id,
                    'action_type' => 'producing',
                    'product_name' => $productName,
                    'batch_number' => $record->batch_number,
                    'start_time' => $now,
                    'employee_ids' => json_encode($userIds),
                    'previous_status' => $prevStatus,
                    'current_status' => 'producing',
                    'remarks' => 'Bắt đầu sản xuất công đoạn: ' . ($dist->section_label ?: $dist->section_id),
                    'created_by' => $currentUserId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            // Tạo campaign dọn quang PHÒNG, gắn distribution_id để completeCampaign biết
            // chốt clearance_completed_at cho đúng phiên sản xuất này.
            $roomCampaign = ClearanceRoomCampaign::create([
                'room_id' => $dist->room_id,
                'distribution_id' => $dist->id,
                'process_list_id' => $roomProcessList->id,
                'status' => 'in_progress',
                'started_by' => $currentUserId,
                'started_at' => $now,
                'employee_ids' => $userIds,
            ]);
            foreach (ClearanceRoomProcess::where('process_list_id', $roomProcessList->id)->orderBy('step')->get() as $s) {
                ClearanceRoomCampaignStep::create([
                    'campaign_id' => $roomCampaign->id,
                    'process_step_id' => $s->id,
                    'step' => $s->step,
                    'is_done' => false,
                ]);
            }

            // Tạo campaign dọn quang cho TỪNG thiết bị trong phòng, neo vào room_campaign_id.
            foreach ($equipRows as $eq) {
                $epl = $equipProcessLists[$eq->id];
                $equipCampaign = ClearanceEquipCampaign::create([
                    'equipment_id' => $eq->id,
                    'room_campaign_id' => $roomCampaign->id,
                    'process_list_id' => $epl->id,
                    'status' => 'in_progress',
                    'started_by' => $currentUserId,
                    'started_at' => $now,
                    'employee_ids' => $userIds,
                ]);
                foreach (ClearanceEquipProcess::where('process_list_id', $epl->id)->orderBy('step')->get() as $s) {
                    ClearanceEquipCampaignStep::create([
                        'campaign_id' => $equipCampaign->id,
                        'process_step_id' => $s->id,
                        'step' => $s->step,
                        'is_checked' => false,
                    ]);
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Lỗi khởi tạo dọn quang: ' . $e->getMessage()]);
        }

        $clearanceUrl = route('pages.manu_env.clearance_process.campaign.open', ['room_id' => $dist->room_id])
            . '?campaign_id=' . $roomCampaign->id;

        return response()->json([
            'success' => true,
            'needs_clearance' => true,
            'message' => 'Đã bắt đầu sản xuất. Vui lòng hoàn tất dọn quang phòng & thiết bị trước khi ghi chép hồ sơ.',
            'redirect_url' => $clearanceUrl,
        ]);
    }

    /**
     * Kết thúc 1 phiên sản xuất đã bắt đầu tại phòng: chốt production_ended_at/ended_by,
     * ghi nhật ký phòng chuyển 'dirty' (cần vệ sinh cho lô/công đoạn kế tiếp). Đây là mốc
     * dừng của "Lịch sử môi trường sản xuất" — command RecordProductionEnvironment chỉ
     * ghi nhận cho các phiên có started_at nhưng CHƯA có production_ended_at.
     */
    public function endProduction(Request $request)
    {
        $validated = $request->validate([
            'distribution_id' => 'required|integer',
        ]);

        $dist = DB::table('ebmr_record_distributions')->where('id', $validated['distribution_id'])->first();
        if (!$dist) {
            return response()->json(['success' => false, 'message' => 'Phân phối không tồn tại hoặc đã bị thu hồi.']);
        }
        if (is_null($dist->started_at)) {
            return response()->json(['success' => false, 'message' => 'Phiên sản xuất này chưa được bắt đầu.']);
        }
        if (!is_null($dist->production_ended_at)) {
            return response()->json(['success' => true, 'already_ended' => true, 'message' => 'Phiên sản xuất đã được kết thúc trước đó.']);
        }

        $currentUserId = (int) (session('user')['userId'] ?? 0);
        $userIds = array_map('intval', json_decode($dist->user_ids ?? '[]', true) ?: []);
        if (!in_array($currentUserId, $userIds, true)) {
            return response()->json(['success' => false, 'message' => 'Bạn không nằm trong danh sách được phân phối công đoạn này.']);
        }

        $now = now();
        DB::table('ebmr_record_distributions')->where('id', $dist->id)->update([
            'production_ended_at' => $now,
            'ended_by' => $currentUserId,
            'updated_at' => $now,
        ]);

        $prevStatus = DB::table('room_logbooks')
            ->where('room_id', $dist->room_id)
            ->whereNull('equipment_id')
            ->orderByDesc('id')
            ->value('current_status') ?? 'producing';

        DB::table('room_logbooks')->insert([
            'room_id' => $dist->room_id,
            'action_type' => 'idle',
            'start_time' => $now,
            'end_time' => $now,
            'employee_ids' => json_encode($userIds),
            'previous_status' => $prevStatus,
            'current_status' => 'dirty',
            'remarks' => 'Kết thúc sản xuất công đoạn: ' . ($dist->section_label ?: $dist->section_id),
            'created_by' => $currentUserId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Tự xuất Báo cáo môi trường sản xuất của phiên này thành PDF và đính kèm vào
        // phân đoạn (ebmr_record_section_attachments). Lỗi xuất báo cáo không được chặn
        // nghiệp vụ kết thúc sản xuất (đã chốt production_ended_at ở trên) — chỉ báo lại.
        $reportMessage = 'Báo cáo môi trường sản xuất (PDF) đã được đính kèm vào phân đoạn.';
        try {
            \App\Services\ProductionEnvironmentReportService::attachReportPdf(
                (int) $dist->id,
                $currentUserId,
                session('user')['fullName'] ?? 'Hệ thống'
            );
        } catch (\Throwable $e) {
            Log::error('endProduction: lỗi xuất báo cáo môi trường PDF cho phiên #' . $dist->id . ': ' . $e->getMessage());
            $reportMessage = 'Lưu ý: xuất báo cáo môi trường PDF thất bại — có thể in thủ công từ Lịch Sử Môi Trường Sản Xuất.';
        }

        return response()->json([
            'success' => true,
            'message' => 'Đã kết thúc sản xuất. Phòng chuyển trạng thái "Cần vệ sinh" cho lô kế tiếp. ' . $reportMessage,
        ]);
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
            // Các công đoạn ĐÃ NỐI TRANG được gộp chung 1 dòng cấu hình ở modal (xem index()) —
            // member_section_ids chứa toàn bộ section_id gốc trong nhóm đó, mặc định về đúng
            // section_id nếu client không gửi (tương thích ngược).
            'distributions.*.member_section_ids' => 'nullable|array',
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

            $memberSectionIds = !empty($dist['member_section_ids'])
                ? array_values(array_unique(array_map('strval', $dist['member_section_ids'])))
                : [(string) $dist['section_id']];

            foreach ($memberSectionIds as $memberSectionId) {
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
                    ->where('section_id', $memberSectionId)
                    ->first();

                if ($existing) {
                    // Phân phối lại sang PHÒNG KHÁC → phiên sản xuất cũ (nếu đã bắt đầu)
                    // không còn giá trị: reset started_at/started_by để buộc bấm "Bắt đầu
                    // sản xuất" lại tại phòng mới (kiểm tra lại vệ sinh & dọn quang).
                    if ((int) $existing->room_id !== (int) $dist['room_id']) {
                        $payload['started_at'] = null;
                        $payload['started_by'] = null;
                    }
                    DB::table('ebmr_record_distributions')->where('id', $existing->id)->update($payload);
                } else {
                    DB::table('ebmr_record_distributions')->insert(array_merge($payload, [
                        'record_id' => $recordId,
                        'section_id' => $memberSectionId,
                        'created_at' => $now,
                    ]));
                }

                // Ghi vết lịch sử (append-only) song song với việc cập nhật trạng thái hiện tại ở trên,
                // để giữ lại toàn bộ các lần phân phối/phân phối lại thay vì chỉ giữ bản mới nhất.
                DB::table('ebmr_record_distribution_logs')->insert([
                    'record_id' => $recordId,
                    'section_id' => $memberSectionId,
                    'section_label' => $dist['section_label'] ?? null,
                    'room_id' => $dist['room_id'],
                    'room_code' => $room->code ?? null,
                    'room_name' => $room->name ?? null,
                    'user_ids' => json_encode(array_values($dist['user_ids'] ?? [])),
                    'distributed_by' => $distributedBy,
                    'distributed_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $count++;
        }

        if ($count === 0) {
            return response()->json(['success' => false, 'message' => 'Vui lòng chọn ít nhất 1 phòng cho 1 công đoạn.']);
        }

        return response()->json(['success' => true, 'message' => "Đã phân phối $count công đoạn thành công"]);
    }

    /**
     * Lịch sử phân phối của 1 hồ sơ (tất cả công đoạn), dùng để hiển thị "Lịch sử phân phối"
     * trong modal Phân Phối Công Đoạn: ai phân phối, phân phối tới phòng nào, cho những ai,
     * và lần thứ mấy của công đoạn đó (sắp xếp tăng dần theo thời gian để đánh số đúng thứ tự).
     */
    public function getRecordDistributionHistory($recordId)
    {
        $logs = DB::table('ebmr_record_distribution_logs')
            ->where('record_id', $recordId)
            ->orderBy('section_id')
            ->orderBy('distributed_at')
            ->orderBy('id')
            ->get();

        $userIds = $logs->pluck('distributed_by')->filter()->unique()->values();
        $userNames = DB::table('user_management')->whereIn('id', $userIds)->pluck('fullName', 'id');

        $seq = [];
        $result = $logs->map(function ($log) use ($userNames, &$seq) {
            $seq[$log->section_id] = ($seq[$log->section_id] ?? 0) + 1;
            $log->attempt_no = $seq[$log->section_id];
            $log->distributed_by_name = $userNames[$log->distributed_by] ?? null;
            $log->user_ids = json_decode($log->user_ids ?? '[]', true) ?: [];
            return $log;
        });

        return response()->json($result);
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

        // Hồ sơ đã hoàn thành/duyệt: KHÔNG cho ghi thêm giá trị nữa (chặn cả POST trực tiếp
        // vòng qua giao diện). Ngoại lệ duy nhất: chuyển trạng thái completed -> reviewed
        // ("Xác nhận đã đọc") với data rỗng.
        $record = DB::table('ebmr_records')->where('id', $validated['record_id'])->first();
        if (!$record) {
            return response()->json(['success' => false, 'message' => 'Hồ sơ không tồn tại.']);
        }
        if (in_array($record->status, ['completed', 'reviewed'])) {
            $isReviewTransition = ($validated['status'] ?? null) === 'reviewed'
                && $record->status === 'completed'
                && empty($dataEntries);
            if (!$isReviewTransition) {
                return response()->json(['success' => false, 'message' => 'Hồ sơ đã khóa (hoàn thành/đã duyệt) — không thể ghi thêm dữ liệu.']);
            }
        }

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

                // Field thuộc GF liên kết (id dạng "{hostBlockId}__gf{...}") — lần đầu ghi dữ liệu
                // vào field này thì chốt (pin) đúng ấn bản GF active hiện tại cho vị trí liên kết đó.
                if (preg_match('/^(\d+)__gf/', (string) $blockUuid, $m)) {
                    \App\Services\LinkedGfResolver::pinIfMissing((int) $validated['record_id'], (int) $m[1], $userId);
                }

                // Nếu value là mảng hoặc đối tượng (dành cho bảng/ô có tọa độ)
                if (is_array($value) || is_object($value)) {
                    foreach ($value as $cellId => $rawValue) {
                        if ($cellId === '_meta') continue;

                        $reason = $reasons[$blockUuid][$cellId] ?? null;

                        // Ô bảng có thể là mảng/đối tượng (vd. block __na__: {reason,by,at,group}),
                        // nên chuẩn hoá về chuỗi TRƯỚC khi so sánh — tránh "Array to string conversion".
                        $rawValueStr = (is_array($rawValue) || is_object($rawValue)) ? json_encode($rawValue) : (string)$rawValue;

                        $existing = DB::table('ebmr_run_data')->where([
                            'record_id' => $validated['record_id'],
                            'block_uuid' => $blockUuid,
                            'cell_id' => $cellId
                        ])->first();

                        $oldRawValue = null;
                        if ($existing) {
                            $oldRawValue = RunDataEncryptionService::decrypt($existing->raw_value);
                            if ((string)$oldRawValue !== $rawValueStr && $oldRawValue !== null && $oldRawValue !== "") {
                                if (empty($reason)) {
                                    throw new \Exception("Vui lòng cung cấp lý do thay đổi dữ liệu.");
                                }
                            }
                        }

                        Log::info("Saving cell: " . $cellId . " = " . $rawValueStr);
                        DB::table('ebmr_run_data')->updateOrInsert(
                            [
                                'record_id' => $validated['record_id'],
                                'block_uuid' => $blockUuid,
                                'cell_id' => $cellId
                            ],
                            [
                                'filled_by' => RunDataEncryptionService::encrypt((string)$userId),
                                'filled_at' => $now,
                                'value'     => RunDataEncryptionService::encryptJson([$cellId => $rawValue]),
                                'raw_value' => RunDataEncryptionService::encrypt($rawValueStr),
                                'updated_at' => $now,
                                'updated_by' => RunDataEncryptionService::encrypt($userName),
                            ]
                        );

                        if ($existing && (string)$oldRawValue !== $rawValueStr) {
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
                            'filled_by' => RunDataEncryptionService::encrypt((string)$userId),
                            'filled_at' => $now,
                            'value'     => RunDataEncryptionService::encryptJson(['text' => $value]),
                            'raw_value' => RunDataEncryptionService::encrypt((string)$value),
                            'updated_at' => $now,
                            'updated_by' => RunDataEncryptionService::encrypt($userName),
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
     * Lưu CẤU TRÚC PHÁT SINH lúc thực thi của 1 block cho 1 lô (hiện là các dòng
     * "Thêm dòng (Cấp 2)"). Client luôn gửi TOÀN BỘ overlay hiện tại của block —
     * rows rỗng nghĩa là đã xoá hết dòng động -> xoá luôn overlay.
     * Template gốc (ebmr_template_blocks) không bị đụng tới.
     */
    public function saveRecordStructure(Request $request)
    {
        $validated = $request->validate([
            'record_id' => 'required',
            'block_uuid' => 'required|string|max:100',
            'kind' => 'required|string|max:30',
            'payload' => 'nullable|array',
        ]);

        $record = DB::table('ebmr_records')->where('id', $validated['record_id'])->first();
        if (!$record) {
            return response()->json(['success' => false, 'message' => 'Hồ sơ không tồn tại.']);
        }
        if (in_array($record->status, ['completed', 'reviewed'])) {
            return response()->json(['success' => false, 'message' => 'Hồ sơ đã khóa (hoàn thành/đã duyệt) — không thể thay đổi cấu trúc.']);
        }

        $payload = $validated['payload'] ?? [];
        $rows = $payload['rows'] ?? [];

        try {
            if (empty($rows)) {
                DB::table('ebmr_record_structures')->where([
                    'record_id' => $validated['record_id'],
                    'block_uuid' => $validated['block_uuid'],
                    'kind' => $validated['kind'],
                ])->delete();
            } else {
                DB::table('ebmr_record_structures')->updateOrInsert(
                    [
                        'record_id' => $validated['record_id'],
                        'block_uuid' => $validated['block_uuid'],
                        'kind' => $validated['kind'],
                    ],
                    [
                        'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('saveRecordStructure error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Lỗi Database: ' . $e->getMessage()]);
        }
    }

    /**
     * Đính kèm 1 tài liệu PDF vào 1 phân đoạn của hồ sơ đang thực thi (khoá
     * record_id + section_id, cùng cách định danh phân đoạn với
     * ebmr_record_distributions/ebmr_record_structures).
     */
    public function uploadSectionAttachment(Request $request)
    {
        $validated = $request->validate([
            'record_id' => 'required|exists:ebmr_records,id',
            'section_id' => 'required|string|max:191',
            'section_label' => 'nullable|string|max:255',
            'title' => 'required|string|max:255',
            'file' => 'required|file|mimes:pdf|max:20480',
        ]);

        $record = DB::table('ebmr_records')->where('id', $validated['record_id'])->first();
        if (in_array($record->status, ['completed', 'reviewed'])) {
            return response()->json(['success' => false, 'message' => 'Hồ sơ đã khóa (hoàn thành/đã duyệt) — không thể đính kèm thêm tài liệu.']);
        }

        $file = $request->file('file');
        // Phải lấy tên gốc/kích thước TRƯỚC khi move() — sau khi move, UploadedFile không
        // còn trỏ tới file vật lý ở đường dẫn cũ nữa nên getSize() sẽ ném lỗi stat failed.
        $originalName = $file->getClientOriginalName();
        $fileSize = $file->getSize();
        $dir = public_path('upLoadData/doc/ebmr_records/' . $validated['record_id']);
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }
        $filename = 'att_' . $validated['record_id'] . '_' . time() . '_' . \Illuminate\Support\Str::random(8) . '.pdf';
        $file->move($dir, $filename);

        $now = now();
        $userId = session('user')['userId'] ?? null;
        $userName = session('user')['fullName'] ?? 'System';
        $id = DB::table('ebmr_record_section_attachments')->insertGetId([
            'record_id' => $validated['record_id'],
            'section_id' => $validated['section_id'],
            'section_label' => $validated['section_label'] ?? null,
            'title' => $validated['title'],
            'file_name' => $originalName,
            'file_path' => '/upLoadData/doc/ebmr_records/' . $validated['record_id'] . '/' . $filename,
            'file_size' => $fileSize,
            'uploaded_by' => $userId,
            'uploaded_by_name' => $userName,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $row = DB::table('ebmr_record_section_attachments')->where('id', $id)->first();
        return response()->json(['success' => true, 'attachment' => $this->formatSectionAttachment($row)]);
    }

    /**
     * Xoá 1 tài liệu đính kèm (file vật lý + bản ghi).
     */
    public function deleteSectionAttachment(Request $request, $id)
    {
        $row = DB::table('ebmr_record_section_attachments')->where('id', $id)->first();
        if (!$row) {
            return response()->json(['success' => false, 'message' => 'Tài liệu không tồn tại.'], 404);
        }

        $record = DB::table('ebmr_records')->where('id', $row->record_id)->first();
        if ($record && in_array($record->status, ['completed', 'reviewed'])) {
            return response()->json(['success' => false, 'message' => 'Hồ sơ đã khóa (hoàn thành/đã duyệt) — không thể xoá tài liệu.']);
        }

        $fullPath = public_path(ltrim($row->file_path, '/'));
        if (file_exists($fullPath)) {
            @unlink($fullPath);
        }
        DB::table('ebmr_record_section_attachments')->where('id', $id)->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Chuẩn hoá 1 dòng ebmr_record_section_attachments thành JSON gửi cho JS
     * (dùng chung giữa execute() khi tải trang và 2 endpoint upload/delete ở trên).
     */
    private function formatSectionAttachment($row): array
    {
        return [
            'id' => $row->id,
            'title' => $row->title,
            'file_name' => $row->file_name,
            'url' => $row->file_path,
            'size_label' => $this->formatFileSize($row->file_size),
            'uploaded_by_name' => $row->uploaded_by_name,
            'uploaded_at' => $row->created_at ? Carbon::parse($row->created_at)->format('d/m/Y H:i') : null,
        ];
    }

    private function formatFileSize($bytes): string
    {
        if (!$bytes) return '';
        if ($bytes < 1024) return $bytes . ' B';
        if ($bytes < 1048576) return round($bytes / 1024, 1) . ' KB';
        return round($bytes / 1048576, 1) . ' MB';
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
