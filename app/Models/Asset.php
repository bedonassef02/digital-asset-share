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
        'disk',
        'extension',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    protected $hidden = [
        'path',
        'disk',
    ];
}
