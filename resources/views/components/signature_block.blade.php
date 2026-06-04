@php
    $wfType = $wfType ?? 'cleaning'; // 'cleaning' or 'ebmr'
    $type = $type ?? 'room'; // 'room' or 'equipment' (only for cleaning)
    $listId = $listId ?? 0;

    $signatureBoxes = [];
    $list = null;
    $workflowsTable = '';
    $foreignKey = '';

    if ($wfType === 'cleaning') {
        if ($type === 'room') {
            $list = \DB::table('cleaning_room_processes_list')->where('id', $listId)->first();
        } else {
            $list = \DB::table('cleaning_equip_processes_list')->where('id', $listId)->first();
        }
        $workflowsTable = 'cleaning_process_workflows';
        $foreignKey = 'process_list_id';
    } elseif ($wfType === 'ebmr') {
        // eBMR Template logic
        $list = \DB::table('ebmr_templates')->where('id', $listId)->first();
        $workflowsTable = 'ebmr_template_workflows';
        $foreignKey = 'template_id';
    }

    if ($list) {
        $creator = \DB::table('user_management')
            ->leftJoin('designations', 'user_management.designation_id', '=', 'designations.id')
            ->select('user_management.*', 'designations.name as designation_name')
            ->where('user_management.id', $list->created_by ?? 0)
            ->first();

        if ($creator) {
            $signatureBoxes[] = [
                'role_title' => 'Người soạn thảo',
                'user_name' => $creator->fullName,
                'designation_name' => $creator->designation_name,
                'date' => date('d.m.Y', strtotime($list->created_at)),
                'signature_image' => $creator->signature_image ?? null,
                'color_class' => '',
            ];
        } else {
            $signatureBoxes[] = [
                'role_title' => 'Người soạn thảo',
                'user_name' => '.......................................',
                'designation_name' => '',
                'date' => '................',
                'signature_image' => null,
                'color_class' => '',
            ];
        }

        $query = \DB::table($workflowsTable)
            ->where($foreignKey, $listId)
            ->join('user_management', "{$workflowsTable}.user_id", '=', 'user_management.id')
            ->leftJoin('designations', 'user_management.designation_id', '=', 'designations.id')
            ->select(
                "{$workflowsTable}.*",
                'user_management.fullName as user_name',
                'user_management.signature_image',
                'designations.name as designation_name',
            )
            ->orderBy("{$workflowsTable}.step_order", 'asc');

        if ($wfType === 'cleaning') {
            $query->where("{$workflowsTable}.type", $type);
        }

        $workflows = $query->get();

        foreach ($workflows as $wf) {
            $roleTitle = '';
            $colorClass = '';
            if ($wf->role === 'reviewer') {
                $roleTitle = 'Người kiểm tra';
                $colorClass = ''; // Black
            } elseif ($wf->role === 'approver') {
                $roleTitle = 'Người phê duyệt';
                $colorClass = ''; // Black
            } elseif ($wf->role === 'authorizer') {
                $roleTitle = 'Người ban hành';
                $colorClass = 'text-danger';
            } else {
                $roleTitle = ucfirst($wf->role);
            }

            $isApproved = $wf->status === 'approved';

            $signatureBoxes[] = [
                'role_title' => $roleTitle,
                'user_name' => $isApproved ? $wf->user_name : '.......................................',
                'designation_name' => $isApproved ? $wf->designation_name : '',
                'date' => $isApproved ? date('d.m.Y', strtotime($wf->updated_at)) : '................',
                'signature_image' => $isApproved ? $wf->signature_image : null,
                'color_class' => $colorClass,
            ];
        }
    } else {
        // Fallback if list not found
        $signatureBoxes[] = [
            'role_title' => 'Người soạn thảo',
            'user_name' => '.......................................',
            'designation_name' => '',
            'date' => '................',
            'signature_image' => null,
            'color_class' => '',
        ];
        $signatureBoxes[] = [
            'role_title' => 'Người kiểm tra / phê duyệt',
            'user_name' => '.......................................',
            'designation_name' => '',
            'date' => '................',
            'signature_image' => null,
            'color_class' => '',
        ];
        $signatureBoxes[] = [
            'role_title' => 'Người ban hành',
            'user_name' => '.......................................',
            'designation_name' => '',
            'date' => '................',
            'signature_image' => null,
            'color_class' => 'text-danger',
        ];
    }
@endphp

<!-- DEBUG INFO: wfType={{ $wfType }}, type={{ $type }}, listId={{ $listId }}, list_exists={{ $list ? 'yes' : 'no' }} -->

<div class="p-4 bg-white border-top mt-3" style="overflow-x: auto;">
    <table class="table table-bordered text-center mb-0"
        style="table-layout: fixed; min-width: {{ count($signatureBoxes) * 200 }}px; border-color: #dee2e6;">
        <tbody>
            <tr>
                @foreach ($signatureBoxes as $box)
                    <td class="p-2">{{ $box['role_title'] }}</td>
                @endforeach
            </tr>
            <tr>
                @foreach ($signatureBoxes as $box)
                    <td style="height: 150px; vertical-align: bottom;" class="pb-2">
                        @if ($box['signature_image'])
                            <div class="mb-2"><img src="{{ $box['signature_image'] }}" alt="Chữ ký"
                                    style="max-height: 80px; mix-blend-mode: multiply;"></div>
                        @endif
                        <div class="fw-bold {{ $box['color_class'] }} mb-1">{{ $box['user_name'] }}</div>
                        <div class="small text-muted mb-2" style="min-height: 20px;">{{ $box['designation_name'] }}
                        </div>
                        {{-- <div class="border-top border-dark mx-4 pt-2">{{ $box['role_title'] }}</div> --}}
                    </td>
                @endforeach
            </tr>
            <tr class="text-start">
                @foreach ($signatureBoxes as $box)
                    <td class="p-2">Ngày: {{ $box['date'] }}</td>
                @endforeach
            </tr>
        </tbody>
    </table>
</div>
