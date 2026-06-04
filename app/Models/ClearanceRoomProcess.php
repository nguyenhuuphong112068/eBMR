<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClearanceRoomProcess extends Model
{
    use HasFactory;

    protected $table = 'clearance_room_processes';

    protected $fillable = [
        'process_list_id', 'step', 'content', 'standard'
    ];

    public function processList()
    {
        return $this->belongsTo(ClearanceRoomProcessList::class, 'process_list_id', 'id');
    }
}
