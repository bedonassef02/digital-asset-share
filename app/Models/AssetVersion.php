<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetVersion extends Model
{
    protected $fillable = [
        'asset_id',
        'version',
        'name',
        'mime_type',
        'size',
        'extension',
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }
}
