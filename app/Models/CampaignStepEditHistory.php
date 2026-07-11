<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CampaignStepEditHistory extends Model
{
    use HasFactory;

    protected $table = 'campaign_step_edit_history';

    protected $fillable = [
        'step_type', 'campaign_step_id',
        'old_is_passed', 'new_is_passed',
        'old_note', 'new_note',
        'old_images', 'new_images',
        'reason', 'changed_by', 'changed_at',
    ];

    protected $casts = [
        'old_is_passed' => 'boolean',
        'new_is_passed' => 'boolean',
        'old_images' => 'array',
        'new_images' => 'array',
        'changed_at' => 'datetime',
    ];
}
