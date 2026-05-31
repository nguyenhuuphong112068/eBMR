<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StageProduction extends Model
{
    protected $table = 'stage_production';

    protected $fillable = [
        'workshop_code',
        'stage_name',
        'stage_codes',
        'icon_class',
        'gradient_class',
        'description',
        'order_num'
    ];

    protected $casts = [
        'stage_codes' => 'array'
    ];
}
