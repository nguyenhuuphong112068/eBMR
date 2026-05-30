<?php

namespace App\Models\masterData\Room;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoomModel extends Model
{
    use HasFactory;

    protected $connection = 'pms';

    protected $table = 'room';

    protected $fillable = [
        'code',
        'name',
        'main_equiment_name',
        'capacity',
        'stage',
        'stage_code',
        'sheet_1',
        'sheet_2',
        'sheet_3',
        'sheet_regular',
        'production_group',
        'group_code',
        'active',
        'order_by',
        'AHU_group',
        'deparment_code',
        'prepareBy',
    ];
}
