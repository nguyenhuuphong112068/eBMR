<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CleaningEquipCampaign extends Model
{
    use HasFactory;

    protected $table = 'cleaning_equip_campaigns';

    protected $fillable = [
        'equipment_id', 'process_list_id', 'room_campaign_id',
        'clean_location', 'source_room_id', 'clearing_room_id',
        'status', 'cleaning_type', 'employee_ids',
        'started_by', 'completed_by', 'started_at', 'completed_at',
    ];

    protected $casts = [
        'started_at'   => 'datetime',
        'completed_at' => 'datetime',
        'employee_ids' => 'array',
    ];

    public function steps()
    {
        return $this->hasMany(CleaningEquipCampaignStep::class, 'campaign_id', 'id')
                    ->orderBy('step', 'asc');
    }

    public function processList()
    {
        return $this->belongsTo(CleaningEquipProcessList::class, 'process_list_id', 'id');
    }

    public function roomCampaign()
    {
        return $this->belongsTo(CleaningRoomCampaign::class, 'room_campaign_id', 'id');
    }

    public function clearingRoom()
    {
        return $this->belongsTo(RoomClearing::class, 'clearing_room_id', 'id');
    }
}
