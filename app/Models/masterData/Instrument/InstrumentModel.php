<?php

namespace App\Models\masterData\Instrument;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstrumentModel extends Model
{
    use HasFactory;

    protected $table = 'instrument';

    protected $fillable = [
        'code',
        'name',
        'stage_id',
        'type',
        'connection_type',
        'ip',
        'port',
        'brand',
        'baud_rate',
        'data_bits',
        'parity',
        'stop_bits',
        'created_by',
    ];
}
