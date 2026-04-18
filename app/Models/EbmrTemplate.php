<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EbmrTemplate extends Model
{
    protected $fillable = ['name', 'schema', 'category'];

    protected $casts = [
        'schema' => 'array',
    ];

    public function records()
    {
        return $this->hasMany(EbmrRecord::class, 'template_id');
    }
}
