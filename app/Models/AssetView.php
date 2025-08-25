<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetView extends Model
{
    protected $fillable = [
        'user_id',
        'viewable_id',
        'viewable_type',
        'last_seen_at',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function viewable()
    {
        return $this->morphTo();
    }
}
