<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Collection extends Model
{
    use HasFactory;
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

    public function shares(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(Share::class, 'shareable');
    }

    public function views(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(AssetView::class, 'viewable');
    }

    public function downloads(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(Download::class, 'downloadable');
    }
}
