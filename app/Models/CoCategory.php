<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoCategory extends Model
{
    protected $table = 'co_category';
    protected $fillable = ['code', 'name', 'active'];
}
