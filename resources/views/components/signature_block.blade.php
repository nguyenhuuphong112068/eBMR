@php
    $wfType = $wfType ?? 'cleaning'; // 'cleaning' or 'ebmr'
    $type = $type ?? 'room';         // 'room' or 'equipment' (only for cleaning)
    $listId = $listId ?? 0;
    
    $creatorName = '.......................................';
    $creatorDate = '................';
    
    $checkerName = '.......................................';
    $checkerDate = '................';
    
    $authorizerName = '.......................................';
    $authorizerDate = '................';

    if ($wfType === 'cleaning') {
        if ($type === 'room') {
            $list = \DB::table('cleaning_room_processes_list')->where('id', $listId)->first();
        } else {
            $list = \DB::table('cleaning_equip_processes_list')->where('id', $listId)->first();
        }
        
        if ($list) {
            $creator = \DB::table('user_management')->where('id', $list->created_by)->first();
            if ($creator) {
                $creatorName = $creator->name;
                $creatorDate = date('d.m.Y', strtotime($list->created_at));
            }
            
            $workflows = \DB::table('cleaning_process_workflows')
                ->where('type', $type)
                ->where('process_list_id', $listId)
                ->join('user_management', 'cleaning_process_workflows.user_id', '=', 'user_management.id')
                ->select('cleaning_process_workflows.*', 'user_management.name as user_name')
                ->get()
                ->keyBy('role');
                
            $reviewer = $workflows['reviewer'] ?? null;
            $approver = $workflows['approver'] ?? null;
            $authorizer = $workflows['authorizer'] ?? null;
            
            $checkerApprover = $approver ?? $reviewer;
            
            if ($checkerApprover && $checkerApprover->status === 'approved') {
                $checkerName = $checkerApprover->user_name;
                $checkerDate = date('d.m.Y', strtotime($checkerApprover->updated_at));
            }
            if ($authorizer && $authorizer->status === 'approved') {
                $authorizerName = $authorizer->user_name;
                $authorizerDate = date('d.m.Y', strtotime($authorizer->updated_at));
            }
        }
    } elseif ($wfType === 'ebmr') {
        // eBMR Template logic
        $template = \DB::table('ebmr_templates')->where('id', $listId)->first();
        if ($template) {
            $creator = \DB::table('user_management')->where('id', $template->created_by ?? 0)->first();
            if ($creator) {
                $creatorName = $creator->name;
                $creatorDate = date('d.m.Y', strtotime($template->created_at));
            }
            
            $workflows = \DB::table('ebmr_template_workflows')
                ->where('template_id', $listId)
                ->join('user_management', 'ebmr_template_workflows.user_id', '=', 'user_management.id')
                ->select('ebmr_template_workflows.*', 'user_management.name as user_name')
                ->get()
                ->keyBy('role');
                
            $reviewer = $workflows['reviewer'] ?? null;
            $approver = $workflows['approver'] ?? null;
            $authorizer = $workflows['authorizer'] ?? null;
            
            $checkerApprover = $approver ?? $reviewer;
            
            if ($checkerApprover && $checkerApprover->status === 'approved') {
                $checkerName = $checkerApprover->user_name;
                $checkerDate = date('d.m.Y', strtotime($checkerApprover->updated_at));
            }
            if ($authorizer && $authorizer->status === 'approved') {
                $authorizerName = $authorizer->user_name;
                $authorizerDate = date('d.m.Y', strtotime($authorizer->updated_at));
            }
        }
    }
@endphp

<!-- DEBUG INFO: wfType={{$wfType}}, type={{$type}}, listId={{$listId}}, list_exists={{$list ? 'yes' : 'no'}} -->

<div class="p-4 bg-white border-top mt-3">
    <table class="table table-bordered text-center mb-0" style="table-layout: fixed; border-color: #dee2e6;">
        <tbody>
            <tr>
                <td class="p-2">Người soạn thảo</td>
                <td class="p-2">Người kiểm tra và phê duyệt</td>
                <td class="p-2">Cho phép ban hành</td>
            </tr>
            <tr>
                <td style="height: 150px; vertical-align: bottom;" class="pb-2">
                    <div class="fw-bold mb-2">{{ $creatorName }}</div>
                    <div class="border-top border-dark mx-4 pt-2">Người soạn thảo</div>
                </td>
                <td style="height: 150px; vertical-align: bottom;" class="pb-2">
                    <div class="fw-bold text-primary mb-2">{{ $checkerName }}</div>
                    <div class="border-top border-dark mx-4 pt-2">Người kiểm tra / phê duyệt</div>
                </td>
                <td style="height: 150px; vertical-align: bottom;" class="pb-2">
                    <div class="fw-bold text-danger mb-2">{{ $authorizerName }}</div>
                    <div class="border-top border-dark mx-4 pt-2">Người ban hành</div>
                </td>
            </tr>
            <tr class="text-start">
                <td class="p-2">Ngày: {{ $creatorDate }}</td>
                <td class="p-2">Ngày: {{ $checkerDate }}</td>
                <td class="p-2">Ngày: {{ $authorizerDate }}</td>
            </tr>
        </tbody>
    </table>
</div>
