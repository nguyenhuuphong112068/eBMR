<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EbmrRecordBms extends Model
{
    protected $table = 'ebmr_records_bms';

    protected $fillable = [
        'ebmr_record_id',
        'stage_code',
        'room_id',
        'distribution_id',
        'temperature',
        'humidity',
        'pressure',
        'is_out_of_bounds',
        'captured_at',
        'recorded_type',
        'created_by',
        'remarks',
    ];

    protected $casts = [
        'is_out_of_bounds' => 'boolean',
        'captured_at' => 'datetime',
    ];
}
