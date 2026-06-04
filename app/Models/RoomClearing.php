<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoomClearing extends Model
{
    use HasFactory;

    protected $table = 'room_clearings';

    protected $fillable = [
        'code', 'name', 'area', 'description', 'status', 'created_by',
    ];

    public function equipCampaigns()
    {
        return $this->hasMany(CleaningEquipCampaign::class, 'clearing_room_id', 'id');
    }
}
