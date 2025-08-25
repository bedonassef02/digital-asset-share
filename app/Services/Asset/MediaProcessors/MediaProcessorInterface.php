<?php

declare(strict_types=1);

namespace App\Services\Asset\MediaProcessors;

use Illuminate\Http\UploadedFile;

interface MediaProcessorInterface
{
    public function canProcess(UploadedFile $file): bool;

    public function process(UploadedFile $file, array &$metadata): void;
}
