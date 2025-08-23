<?php

namespace App\Models;

use App\Services\AssetStorageService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Asset extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'latest_version_id',
    ];

    protected $appends = [
        'metadata',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getMetadataAttribute(): array
    {
        if (!$this->latestVersion) {
            return [];
        }

        $assetStorageService = app(AssetStorageService::class);
        $metadataFilePath = $assetStorageService->getAssetVersionPath($this->id, $this->latestVersion->version) . '/metadata.json';

        if (Storage::disk()->exists($metadataFilePath)) {
            $metadataContent = Storage::disk()->get($metadataFilePath);
            return json_decode($metadataContent, true);
        }

        return [];
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
        return $this->hasOne(AssetVersion::class)->latest('version');
    }

    public function shares(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Share::class);
    }
}