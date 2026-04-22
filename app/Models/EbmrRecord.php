<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EbmrRecord extends Model
{
    protected $fillable = ['template_id', 'batch_number', 'data', 'created_by', 'status'];

    protected $casts = [
        'data' => 'array',
    ];

    public function template()
    {
        return $this->belongsTo(EbmrTemplate::class);
    }
}
