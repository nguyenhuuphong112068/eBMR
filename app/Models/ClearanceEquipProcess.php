<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClearanceEquipProcess extends Model
{
    use HasFactory;

    protected $table = 'clearance_equip_processes';

    protected $fillable = [
        'process_list_id', 'step', 'content', 'standard'
    ];

    public function processList()
    {
        return $this->belongsTo(ClearanceEquipProcessList::class, 'process_list_id', 'id');
    }
}
