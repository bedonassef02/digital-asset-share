<?php

declare(strict_types=1);

namespace App\Services\Asset;

use App\Services\Asset\MediaProcessors\MediaProcessorInterface;
use Illuminate\Http\UploadedFile;

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
