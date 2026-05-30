<?php

namespace App\Models\masterData\ManuCondition;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ManuConditionModel extends Model
{
    use HasFactory;

    protected $table = 'manu_condition';

    protected $fillable = [
        'room_id',
        'name',
        
        'temp_op_1', 'temp_val1_1', 'temp_val2_1', 'temp_min_1', 'temp_max_1',
        'temp_op_2', 'temp_val1_2', 'temp_val2_2', 'temp_min_2', 'temp_max_2',
        
        'humidity_op_1', 'humidity_val1_1', 'humidity_val2_1', 'humidity_min_1', 'humidity_max_1',
        'humidity_op_2', 'humidity_val1_2', 'humidity_val2_2', 'humidity_min_2', 'humidity_max_2',
        
        'diff_press_corridor_op', 'diff_press_corridor_val1', 'diff_press_corridor_val2', 'diff_press_corridor_min', 'diff_press_corridor_max',
        'diff_press_pal_op', 'diff_press_pal_val1', 'diff_press_pal_val2', 'diff_press_pal_min', 'diff_press_pal_max',
        'diff_press_mal_op', 'diff_press_mal_val1', 'diff_press_mal_val2', 'diff_press_mal_min', 'diff_press_mal_max',
        
        'hepa_filter_op', 'hepa_filter_val1', 'hepa_filter_val2', 'hepa_filter_min', 'hepa_filter_max',
        
        'note',
        'created_by',
    ];
}
