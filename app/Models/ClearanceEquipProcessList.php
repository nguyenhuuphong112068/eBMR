<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClearanceEquipProcessList extends Model
{
    use HasFactory;

    protected $table = 'clearance_equip_processes_list';
    
    protected $fillable = [
        'equipment_id', 'process_code', 'process_name', 'version', 'status', 'created_by', 'clearance_type', 'effective_date'
    ];

    public function processes()
    {
        return $this->hasMany(ClearanceEquipProcess::class, 'process_list_id', 'id')->orderBy('step', 'asc');
    }
}
