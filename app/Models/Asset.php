<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    protected $fillable = [
        'name',
        'description',
        'file_path',
        'mime_type',
        'size',
        'disk',
    ];
}
