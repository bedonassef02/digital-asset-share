<?php

namespace App\Services\MediaProcessors;

use Illuminate\Http\UploadedFile;

interface MediaProcessorInterface
{
    public function canProcess(UploadedFile $file): bool;
    public function process(UploadedFile $file, array &$metadata): void;
}
