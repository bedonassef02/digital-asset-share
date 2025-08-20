<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Asset extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'mime_type',
        'size',
        'extension',
    ];

    protected $appends = [
        'metadata',
    ];

    public function getMetadataAttribute(): array
    {
        $metadataFilePath = 'uploads/' . $this->id . '/metadata.json';

        if (Storage::disk()->exists($metadataFilePath)) {
            $metadataContent = Storage::disk()->get($metadataFilePath);
            return json_decode($metadataContent, true);
        }

        return [];
    }
}