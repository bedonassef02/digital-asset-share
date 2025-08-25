<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
