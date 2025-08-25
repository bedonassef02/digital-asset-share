<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Asset extends Model
{
    use SoftDeletes, HasFactory;

    const STATUS_ACTIVE = 'active';
    const STATUS_ARCHIVED = 'archived';
    const STATUS_DELETED = 'deleted';

    protected $fillable = [
        'user_id',
        'latest_version_id',
        'status',
    ];

    

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tags(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function versions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(AssetVersion::class);
    }

    public function latestVersion(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(AssetVersion::class, 'id', 'latest_version_id');
    }

    public function shares(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(Share::class, 'shareable');
    }

    public function views(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(AssetView::class, 'viewable');
    }

    public function collections(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Collection::class);
    }

    public function downloads(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(Download::class, 'downloadable');
    }
}