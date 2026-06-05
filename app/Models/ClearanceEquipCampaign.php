<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClearanceEquipCampaign extends Model
{
    use HasFactory;

    protected $table = 'clearance_equip_campaigns';

    protected $fillable = [
        'equipment_id', 'process_list_id', 'status',
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
        return $this->hasMany(ClearanceEquipCampaignStep::class, 'campaign_id', 'id')
                    ->orderBy('step', 'asc');
    }

    public function processList()
    {
        return $this->belongsTo(ClearanceEquipProcessList::class, 'process_list_id', 'id');
    }
}
