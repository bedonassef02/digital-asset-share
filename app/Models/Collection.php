<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Collection extends Model
{
    protected $fillable = [
        'name',
        'description',
        'user_id',
        'parent_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function assets()
    {
        return $this->belongsToMany(Asset::class);
    }

    public function parent()
    {
        return $this->belongsTo(Collection::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Collection::class, 'parent_id');
    }
}
