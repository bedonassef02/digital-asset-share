<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetView extends Model
{
    protected $fillable = [
        'user_id',
        'asset_id',
        'last_seen_at',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }
}