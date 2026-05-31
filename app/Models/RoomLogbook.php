<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoomLogbook extends Model
{
    protected $table = 'room_logbooks';

    protected $fillable = [
        'room_id',
        'action_type',
        'product_name',
        'batch_number',
        'start_time',
        'end_time',
        'employee_ids',
        'clean_level',
        'clean_expiry_date',
        'previous_status',
        'current_status',
        'remarks',
        'created_by'
    ];

    protected $casts = [
        'employee_ids' => 'array',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'clean_expiry_date' => 'datetime'
    ];
}
