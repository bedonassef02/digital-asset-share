<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_id',
        'version',
        'name',
        'mime_type',
        'size',
        'extension',
        'description',
        'metadata',
        'file_hash',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }
}
