<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AIService
{
    /**
     * Xử lý tin nhắn và trả về phản hồi từ AI (Tích hợp Ollama)
     */
    public static function getResponse($message, $groupId = null)
    {
        $originalMessage = $message;
        $message = mb_strtolower($message);

        // 1. Phản hồi chào hỏi cơ bản
        if (preg_match('/(chào|hello|hi|bạn là ai)/u', $message)) {
            return "Xin chào! Tôi là **AI Agent eBMR**. Tôi được tạo ra để giúp bạn tìm kiếm thông tin nhanh trong hệ thống quản lý sản xuất (PMS) và hỗ trợ soạn thảo hồ sơ lô điện tử (eBMR).\n\nBạn có thể hỏi tôi về:\n- Danh mục bảo trì/hiệu chuẩn\n- Thông tin máy móc\n- Kế hoạch sản xuất\n- Hoặc chat bất kỳ điều gì!";
        }

        // 2. Tra cứu Kế hoạch sản xuất
        if (preg_match('/(kế hoạch|sản lượng) (.+?) của (.+)/u', $message, $matches)) {
            $timeInfo = trim($matches[2]);
            $dept = trim($matches[3]);
            return self::searchProductionPlan($timeInfo, $dept);
        }

        // 3. Tra cứu tri thức hệ thống từ Metadata
        if (preg_match('/(bạn biết gì về|thông tin về|bảng|dữ liệu) (.+)/u', $message, $matches)) {
            $topic = trim($matches[2]);
            $topic = str_replace(['?', '.', '!'], '', $topic);
            $topic = preg_replace('/^(bảng|dữ liệu|thông tin) /u', '', $topic);
            $topic = trim($topic);

            return self::searchMetadata($topic);
        }

        // 4. Nếu không khớp các lệnh đặc biệt, chuyển sang dùng Ollama
        return self::getOllamaResponse($originalMessage, $groupId);
    }

    /**
     * Gọi Ollama để trả lời chat tự do và hỗ trợ Tool Calling
     */
    private static function getOllamaResponse($prompt, $groupId = null)
    {
        try {
            $ollamaUrl = env('OLLAMA_URL', 'http://localhost:11434');
            $ollamaModel = env('OLLAMA_MODEL', 'qwen2.5:14b');

            $tools = [
                [
                    "type" => "function",
                    "function" => [
                        "name" => "search_ebmr_records",
                        "description" => "Tìm kiếm danh sách hồ sơ lô (eBMR) dựa trên mã lô hoặc trạng thái.",
                        "parameters" => [
                            "type" => "object",
                            "properties" => [
                                "batch_number" => ["type" => "string", "description" => "Mã lô thuốc (ví dụ: BATCH-001)"],
                                "status" => ["type" => "string", "description" => "Trạng thái lô"]
                            ]
                        ]
                    ]
                ],
                [
                    "type" => "function",
                    "function" => [
                        "name" => "get_ebmr_details",
                        "description" => "Lấy chi tiết toàn bộ dữ liệu của một hồ sơ lô cụ thể.",
                        "parameters" => [
                            "type" => "object",
                            "properties" => [
                                "batch_number" => ["type" => "string", "description" => "Mã lô thuốc bắt buộc"]
                            ],
                            "required" => ["batch_number"]
                        ]
                    ]
                ],
                [
                    "type" => "function",
                    "function" => [
                        "name" => "search_system_data",
                        "description" => "Tìm kiếm dữ liệu gốc rải rác trên toàn hệ thống (Sản phẩm, Bán thành phẩm, Nội dung hồ sơ) bằng một từ khóa chung.",
                        "parameters" => [
                            "type" => "object",
                            "properties" => [
                                "keyword" => ["type" => "string", "description" => "Từ khóa cần tìm (VD: Paracetamol, BATCH-001, PXVH)"]
                            ],
                            "required" => ["keyword"]
                        ]
                    ]
                ]
            ];

            // Tích hợp Knowledge Base (Giai đoạn 3: Tự học)
            $knowledges = \Illuminate\Support\Facades\DB::table('ai_knowledge_base')->where('is_active', true)->get();
            $injectedKnowledge = [];
            foreach ($knowledges as $kb) {
                if (mb_stripos($prompt, $kb->keyword, 0, 'UTF-8') !== false) {
                    $injectedKnowledge[] = "- KHI NGƯỜI DÙNG HỎI VỀ '{$kb->keyword}': {$kb->content}";
                }
            }

            $systemContent = "Bạn là Trợ lý AI nội bộ của dự án eBMR. Bạn LUÔN LUÔN PHẢI TRẢ LỜI BẰNG TIẾNG VIỆT trong mọi trường hợp.\n"
                           . "QUY TẮC CỐT LÕI (BẮT BUỘC TUÂN THỦ):\n"
                           . "1. Khi người dùng yêu cầu tìm kiếm, tra cứu, hoặc hỏi thông tin về lô, sản phẩm, nhân sự, bạn BẮT BUỘC PHẢI GỌI CÔNG CỤ (TOOL CALLING) ngay lập tức.\n"
                           . "2. TUYỆT ĐỐI KHÔNG BAO GIỜ trả lời bằng văn bản kiểu 'Em đang tìm kiếm...', 'Vui lòng đợi...', 'Em sẽ kiểm tra...'. Điều này là CẤM KỴ.\n"
                           . "3. Chỉ được phép trả lời bằng văn bản SAU KHI đã gọi tool và nhận được kết quả. Nếu không có dữ liệu, hãy nói 'Em chưa tìm thấy thông tin'.\n"
                           . "4. Khi người dùng hỏi về tên người phụ trách, người kiểm tra, người duyệt của một hồ sơ/sản phẩm (ví dụ Stadgentri), BẠN PHẢI SỬ DỤNG TOOL `search_system_data` thay vì tool tìm lô, vì tên thuốc không phải là mã lô.\n"
                           . "5. Bạn có 3 công cụ: tìm hồ sơ lô, lấy chi tiết lô, và tìm kiếm chung toàn hệ thống.";
            if (!empty($injectedKnowledge)) {
                $systemContent .= "\n\nTHÔNG TIN ĐẶC BIỆT MÀ BẠN CẦN GHI NHỚ VÀ SỬ DỤNG ĐỂ TRẢ LỜI NGAY:\n" . implode("\n", $injectedKnowledge);
            }

            $messages = [
                [
                    "role" => "system",
                    "content" => $systemContent
                ]
            ];

            // Tích hợp Trí nhớ (Lịch sử hội thoại theo Chủ đề/Group)
            if ($groupId) {
                $history = \Illuminate\Support\Facades\DB::table('chat_messages')
                    ->where('group_id', $groupId)
                    ->where('is_recalled', 0)
                    ->orderBy('created_at', 'desc')
                    ->limit(50)
                    ->get()
                    ->reverse();

                foreach ($history as $msg) {
                    if (!$msg->message) continue;
                    $messages[] = [
                        "role" => ($msg->sender_id == 9999) ? "assistant" : "user",
                        "content" => $msg->message
                    ];
                }
            }

            // Nếu không có lịch sử (chạy test), tự động chèn tin nhắn hiện tại
            if (!$groupId) {
                $messages[] = ["role" => "user", "content" => $prompt];
            }

            $payload = [
                "model" => $ollamaModel,
                "messages" => $messages,
                "tools" => $tools,
                "stream" => false
            ];

            $response = \Illuminate\Support\Facades\Http::timeout(180)->withoutVerifying()
                ->post("$ollamaUrl/api/chat", $payload);

            if ($response->failed()) {
                return "Rất tiếc, tôi (AI Agent) không thể kết nối tới máy chủ AI (Ollama) lúc này.";
            }

            $result = $response->json();

            // Xử lý nếu AI gọi Tool
            if (isset($result['message']['tool_calls']) && count($result['message']['tool_calls']) > 0) {
                $toolCall = $result['message']['tool_calls'][0];
                $functionName = $toolCall['function']['name'];
                $arguments = is_array($toolCall['function']['arguments']) ? $toolCall['function']['arguments'] : json_decode($toolCall['function']['arguments'], true);

                $toolResponse = [];
                if ($functionName === 'search_ebmr_records') {
                    $toolResponse = self::toolSearchEbmrRecords($arguments);
                } elseif ($functionName === 'get_ebmr_details') {
                    $toolResponse = self::toolGetEbmrRecordDetails($arguments);
                } elseif ($functionName === 'search_system_data') {
                    $toolResponse = self::toolSearchSystemData($arguments);
                }

                // Phản hồi lại kết quả tool cho AI
                $payload['messages'][] = $result['message'];
                $payload['messages'][] = [
                    "role" => "tool",
                    "content" => json_encode($toolResponse, JSON_UNESCAPED_UNICODE),
                    "name" => $functionName
                ];

                $finalResponse = \Illuminate\Support\Facades\Http::timeout(180)->withoutVerifying()
                    ->post("$ollamaUrl/api/chat", $payload);
                $finalContent = $finalResponse->json()['message']['content'] ?? "Lỗi xử lý kết quả tool.";
                self::logUnhandledQuery($prompt, $finalContent);
                return $finalContent;
            }

            $finalContent = $result['message']['content'] ?? "Tôi không nhận được phản hồi từ AI.";
            self::logUnhandledQuery($prompt, $finalContent);
            return $finalContent;
        } catch (\Exception $e) {
            \Log::error('AI Service Error: ' . $e->getMessage());
            return "Lỗi AI Chat: Hệ thống trợ lý AI đang bận.";
        }
    }

    /**
     * Helper: Log câu hỏi AI không hiểu để admin dạy thêm
     */
    private static function logUnhandledQuery($userQuery, $aiResponse)
    {
        $aiResponseLower = mb_strtolower($aiResponse);
        if (str_contains($aiResponseLower, 'chưa tìm thấy') || str_contains($aiResponseLower, 'không có dữ liệu')) {
            \Illuminate\Support\Facades\DB::table('ai_unhandled_queries')->insert([
                'user_name' => session('user')['fullName'] ?? 'Unknown',
                'query_text' => $userQuery,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }

    /**
     * Tool: Thực hiện truy vấn DB tìm hồ sơ lô
     */
    private static function toolSearchEbmrRecords($args)
    {
        $query = \App\Models\EbmrRecord::query();
        if (isset($args['batch_number'])) {
            $query->where('batch_number', 'like', '%' . $args['batch_number'] . '%');
        }
        if (isset($args['status'])) {
            $query->where('status', $args['status']);
        }
        $records = $query->orderBy('created_at', 'desc')->limit(5)->get();

        if ($records->isEmpty()) return ['message' => 'Không tìm thấy hồ sơ lô nào.'];

        return $records->map(function ($record) {
            return [
                'batch_number' => $record->batch_number,
                'status' => $record->status,
                'created_at' => $record->created_at->format('Y-m-d')
            ];
        })->toArray();
    }

    /**
     * Tool: Lấy chi tiết hồ sơ lô
     */
    private static function toolGetEbmrRecordDetails($args)
    {
        if (!isset($args['batch_number'])) return ['error' => 'Thiếu mã lô thuốc'];

        $record = \App\Models\EbmrRecord::where('batch_number', $args['batch_number'])->first();
        if (!$record) return ['error' => 'Hồ sơ lô không tồn tại'];

        return [
            'batch_number' => $record->batch_number,
            'status' => $record->status,
            'details_data' => $record->data
        ];
    }

    /**
     * Tool: Tìm kiếm chung toàn hệ thống (Dữ liệu rải rác)
     */
    private static function toolSearchSystemData($args)
    {
        if (!isset($args['keyword'])) return ['error' => 'Thiếu từ khóa tìm kiếm (keyword)'];
        $kw = $args['keyword'];
        $results = [];

        // 1. Tìm Bán thành phẩm
        $inter = \Illuminate\Support\Facades\DB::table('intermediate_category')
            ->where('intermediate_code', 'like', "%$kw%")
            ->orWhere('dosage_id', 'like', "%$kw%")
            ->select('intermediate_code', 'dosage_id', 'batch_size', 'unit_batch_size')
            ->limit(2)->get();
        if ($inter->isNotEmpty()) $results['ban_thanh_pham'] = $inter->toArray();

        // 2. Tìm Thành phẩm
        $finish = \Illuminate\Support\Facades\DB::table('finished_product_category')
            ->where('finished_product_code', 'like', "%$kw%")
            ->select('finished_product_code', 'batch_qty', 'unit_batch_qty', 'primary_parkaging')
            ->limit(2)->get();
        if ($finish->isNotEmpty()) $results['thanh_pham'] = $finish->toArray();

        // 3. Tìm Hồ sơ mẫu (Templates) và người phụ trách
        $productIds = \Illuminate\Support\Facades\DB::table('product_name')->where('name', 'like', "%$kw%")->pluck('id');
        if ($productIds->isNotEmpty()) {
            $interCatIds = \Illuminate\Support\Facades\DB::table('intermediate_category')->whereIn('product_name_id', $productIds)->pluck('id');
            $finCatIds = \Illuminate\Support\Facades\DB::table('finished_product_category')->whereIn('product_name_id', $productIds)->pluck('id');

            $templates = \Illuminate\Support\Facades\DB::table('ebmr_templates as t')
                ->leftJoin('user_management as u', 't.owner_id', '=', 'u.id')
                ->where(function($q) use ($interCatIds, $finCatIds) {
                    $q->where(function($q1) use ($interCatIds) {
                        $q1->where('t.type', 'BMR')->whereIn('t.caterogy_id', $interCatIds);
                    })->orWhere(function($q2) use ($finCatIds) {
                        $q2->where('t.type', 'BPR')->whereIn('t.caterogy_id', $finCatIds);
                    });
                })
                ->select('t.id', 't.version', 't.type', 't.status', 'u.fullName as nguoi_phu_trach_ho_so', 't.issued_date')
                ->limit(3)->get();

            if ($templates->isNotEmpty()) {
                $results['ho_so_mau_templates'] = $templates->toArray();
            }
        }

        // 4. Tìm Khối nội dung hồ sơ (Content Blocks)
        $blocks = \Illuminate\Support\Facades\DB::table('ebmr_content_blocks')
            ->where('title', 'like', "%$kw%")
            ->orWhere('vn_contents', 'like', "%$kw%")
            ->select('title', 'vn_contents')
            ->limit(1)->get();
        if ($blocks->isNotEmpty()) {
            // Giới hạn độ dài nội dung để không tràn token
            $results['noi_dung_ho_so'] = $blocks->map(function ($b) {
                return ['title' => $b->title, 'content_preview' => mb_substr(strip_tags($b->vn_contents), 0, 500)];
            })->toArray();
        }

        if (empty($results)) {
            return ['status' => 'Không tìm thấy dữ liệu nào liên quan đến từ khóa này trong các danh mục hệ thống.'];
        }

        return $results;
    }

    /**
     * Tra cứu Kế hoạch sản xuất
     */
    private static function searchProductionPlan($timeInfo, $dept)
    {
        // Chuẩn hóa tên phân xưởng (ví dụ PXVH)
        $dept = mb_strtoupper($dept);

        // Tìm kế hoạch trong plan_list dựa trên tên (tháng) và mã phân xưởng
        $plan = DB::table('plan_list')
            ->where('deparment_code', 'like', "%{$dept}%")
            ->where('name', 'like', "%{$timeInfo}%")
            ->where('active', 1)
            ->first();

        if (!$plan) {
            return "Tôi không tìm thấy kế hoạch nào khớp với '**{$timeInfo}**' của bộ phận '**{$dept}**'.\nBạn hãy thử lại với: *'Kế hoạch tháng 04 của PXVH'*.";
        }

        // Lấy danh sách sản lượng lý thuyết từng lô từ plan_master
        $stats = DB::table('plan_master as pm')
            ->join('finished_product_category as fpc', 'pm.product_caterogy_id', '=', 'fpc.id')
            ->where('pm.plan_list_id', $plan->id)
            ->where('pm.active', 1)
            ->where('pm.cancel', 0)
            ->select(
                DB::raw('COUNT(pm.id) as total_batches'),
                DB::raw('SUM(fpc.batch_qty) as total_qty'),
                'fpc.unit_batch_qty'
            )
            ->groupBy('fpc.unit_batch_qty')
            ->first();

        if (!$stats) {
            return "Kế hoạch '**{$plan->name}**' đã được tìm thấy, nhưng hiện tại chưa có dữ liệu lô sản xuất chi tiết bên trong.";
        }

        $formattedQty = number_format($stats->total_qty, 2);

        return "Dựa trên dữ liệu hệ thống, tôi tìm thấy thông tin sau cho **{$dept}**:\n" .
            "- **Kế hoạch**: {$plan->name}\n" .
            "- **Tổng số lô dự kiến**: {$stats->total_batches} lô\n" .
            "- **Tổng sản lượng lý thuyết**: {$formattedQty} {$stats->unit_batch_qty}\n" .
            "- **Trạng thái**: " . ($plan->send ? 'Đã gởi' : 'Đang dự thảo') . "\n" .
            "- **Người lập**: {$plan->prepared_by}";
    }

    /**
     * Tra cứu Metadata (Tri thức hệ thống)
     */
    private static function searchMetadata($topic)
    {
        $info = DB::table('ai_metadata')
            ->where('table_name', 'like', "%{$topic}%")
            ->orWhere('keywords', 'like', "%{$topic}%")
            ->orWhere('description', 'like', "%{$topic}%")
            ->first();

        if (!$info) {
            return "Rất tiếc, tôi chưa có thông tin chi tiết về chủ đề '**{$topic}**' trong Metadata. Bạn có thể bổ sung dữ liệu vào bảng `ai_metadata` để giúp tôi thông minh hơn nhé!";
        }

        return "Về '**{$topic}**', tôi biết các thông tin sau:\n" .
            "- **Mô tả**: {$info->description}\n" .
            "- **Các cột dữ liệu chính**: {$info->key_columns}\n" .
            "- **Từ khóa liên quan**: {$info->keywords}";
    }

    /**
     * Tìm kiếm thiết bị trong các bảng Inst_Master
     */
    private static function searchInstruments($keyword)
    {
        // (Giữ nguyên logic cũ của searchInstruments bên dưới)
        $results = collect();
        $connections = ['cal1', 'cal2'];

        foreach ($connections as $conn) {
            for ($i = 1; $i <= 3; $i++) {
                try {
                    $items = DB::connection($conn)->table("Inst_Master_{$i}")
                        ->where('Inst_id', 'like', "%{$keyword}%")
                        ->orWhere('Inst_Name', 'like', "%{$keyword}%")
                        ->limit(3)
                        ->get();
                    $results = $results->merge($items);
                } catch (\Exception $e) {
                    continue;
                }
            }
        }

        if ($results->isEmpty()) {
            return "Rất tiếc, tôi không tìm thấy thiết bị nào liên quan đến từ khóa '**{$keyword}**'.";
        }

        $response = "Tôi tìm thấy " . $results->count() . " kết quả phù hợp cho bạn:\n";
        foreach ($results->take(5) as $item) {
            $response .= "\n- **{$item->Inst_id}**: {$item->Inst_Name} (Vị trí: {$item->Inst_Installed_Location})";
        }

        return $response;
    }
}
