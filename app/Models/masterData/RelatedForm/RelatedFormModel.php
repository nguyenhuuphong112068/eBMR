<?php

namespace App\Models\masterData\RelatedForm;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RelatedFormModel extends Model
{
    use HasFactory;

    protected $table = 'Realated_Form_of_room';

    protected $fillable = [
        'room_id',
        'ebmr_templace_id',
        'type',
        'created_by',
    ];

    public function room()
    {
        return $this->belongsTo(\App\Models\masterData\Room\RoomModel::class, 'room_id');
    }
}
