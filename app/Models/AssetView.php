<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetView extends Model
{
    use HasFactory;

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
