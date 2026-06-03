<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CleaningEquipProcess extends Model
{
    use HasFactory;

    protected $table = 'cleaning_equip_processes';

    protected $fillable = [
        'process_list_id', 'step', 'content', 'standard'
    ];

    public function processList()
    {
        return $this->belongsTo(CleaningEquipProcessList::class, 'process_list_id', 'id');
    }
}
