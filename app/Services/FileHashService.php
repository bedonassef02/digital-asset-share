<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;

class FileHashService
{
    public function calculateFileHash(UploadedFile $file): string
    {
        return hash_file('sha256', $file->getRealPath());
    }
}
