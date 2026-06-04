<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClearanceProcessWorkflow extends Model
{
    use HasFactory;

    protected $table = 'clearance_process_workflows';

    protected $fillable = [
        'process_list_id', 'user_id', 'role', 'step_order', 'status'
    ];

    public function processList()
    {
        return $this->belongsTo(ClearanceRoomProcessList::class, 'process_list_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
