<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use App\Services\MediaProcessors\MediaProcessorInterface;

class MediaService
{
    /**
     * @var MediaProcessorInterface[]
     */
    private array $processors;

    public function __construct(MediaProcessorInterface ...$processors)
    {
        $this->processors = $processors;
    }

    public function setProperties(UploadedFile $file, array &$metadata): void
    {
        foreach ($this->processors as $processor) {
            if ($processor->canProcess($file)) {
                $processor->process($file, $metadata);
                return;
            }
        }
    }
}
