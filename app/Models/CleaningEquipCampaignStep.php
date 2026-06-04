<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CleaningEquipCampaignStep extends Model
{
    use HasFactory;

    protected $table = 'cleaning_equip_campaign_steps';

    protected $fillable = [
        'campaign_id', 'process_step_id', 'step',
        'is_done', 'is_passed', 'result_note', 'attached_images',
        'done_by', 'done_at',
    ];

    protected $casts = [
        'is_done'         => 'boolean',
        'is_passed'       => 'boolean',
        'attached_images' => 'array',
        'done_at'         => 'datetime',
    ];

    public function campaign()
    {
        return $this->belongsTo(CleaningEquipCampaign::class, 'campaign_id', 'id');
    }

    public function doneByUser()
    {
        return $this->belongsTo(\App\Models\User::class, 'done_by', 'id');
    }
}
