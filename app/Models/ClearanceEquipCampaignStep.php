<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClearanceEquipCampaignStep extends Model
{
    use HasFactory;

    protected $table = 'clearance_equip_campaign_steps';

    // Cột thật của bảng (khác clearance_room_campaign_steps): is_checked/checked_by/
    // checked_at/notes thay vì is_done/done_by/done_at/result_note.
    protected $fillable = [
        'campaign_id', 'process_step_id', 'step',
        'is_checked', 'is_passed', 'notes', 'attached_images', 'checked_by', 'checked_at'
    ];

    protected $casts = [
        'is_checked' => 'boolean',
        'is_passed' => 'boolean',
        'attached_images' => 'array',
        'checked_at' => 'datetime',
    ];

    public function campaign()
    {
        return $this->belongsTo(ClearanceEquipCampaign::class, 'campaign_id', 'id');
    }

    public function doneByUser()
    {
        return $this->belongsTo(User::class, 'checked_by', 'id');
    }
}
