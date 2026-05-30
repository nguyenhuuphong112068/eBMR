<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EbmrTemplateRoom extends Model
{
    protected $table = 'ebmr_template_rooms';

    protected $fillable = [
        'template_id',
        'section_id',
        'room_id',
        'condition_id',
    ];
}
