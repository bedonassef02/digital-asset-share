<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Asset;

class Tag extends Model
{
    protected $fillable = ['name'];

    public function assets()
    {
        return $this->belongsToMany(Asset::class);
    }
}
