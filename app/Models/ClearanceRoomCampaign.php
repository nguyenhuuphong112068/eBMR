<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClearanceRoomCampaign extends Model
{
    use HasFactory;

    protected $table = 'clearance_room_campaigns';

    protected $fillable = [
        'room_id', 'process_list_id', 'status',
        'started_by', 'completed_by', 'started_at', 'completed_at', 'employee_ids'
    ];

    protected $casts = [
        'started_at'   => 'datetime',
        'completed_at' => 'datetime',
        'is_done'      => 'boolean',
        'employee_ids' => 'array',
    ];

    public function steps()
    {
        return $this->hasMany(ClearanceRoomCampaignStep::class, 'campaign_id', 'id')
                    ->orderBy('step', 'asc');
    }

    public function processList()
    {
        return $this->belongsTo(ClearanceRoomProcessList::class, 'process_list_id', 'id');
    }
}
