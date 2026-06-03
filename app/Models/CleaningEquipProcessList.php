<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CleaningEquipProcessList extends Model
{
    use HasFactory;

    protected $table = 'cleaning_equip_processes_list';
    
    protected $fillable = [
        'equipment_id', 'process_code', 'process_name', 'version', 'status', 'created_by', 'cleaning_type'
    ];

    public function processes()
    {
        return $this->hasMany(CleaningEquipProcess::class, 'process_list_id', 'id')->orderBy('step', 'asc');
    }
}
