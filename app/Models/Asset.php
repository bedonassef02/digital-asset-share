<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    protected $fillable = [
        'name',
        'description',
        'path',
        'url',
        'mime_type',
        'size',
        'extension',
    ];

    protected $hidden = [
        'path',
        'disk',
    ];
}
