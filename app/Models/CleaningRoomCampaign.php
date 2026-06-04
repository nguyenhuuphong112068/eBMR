<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CleaningRoomCampaign extends Model
{
    use HasFactory;

    protected $table = 'cleaning_room_campaigns';

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
        return $this->hasMany(CleaningRoomCampaignStep::class, 'campaign_id', 'id')
                    ->orderBy('step', 'asc');
    }

    public function processList()
    {
        return $this->belongsTo(CleaningRoomProcessList::class, 'process_list_id', 'id');
    }

    public function equipCampaigns()
    {
        return $this->hasMany(CleaningEquipCampaign::class, 'room_campaign_id', 'id');
    }
}
