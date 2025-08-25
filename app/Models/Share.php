<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Share extends Model
{
    use HasFactory;

    protected $fillable = [
        'token',
        'shareable_id',
        'shareable_type',
        'user_id',
        'expires_at',
        'password',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function shareable()
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
