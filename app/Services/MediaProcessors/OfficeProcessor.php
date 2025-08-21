<?php

namespace App\Services\MediaProcessors;

use Illuminate\Http\UploadedFile;

class OfficeProcessor implements MediaProcessorInterface
{
    /**
     * @var MediaProcessorInterface[]
     */
    private array $processors;

    public function __construct(MediaProcessorInterface ...$processors)
    {
        $this->processors = $processors;
    }

    public function canProcess(UploadedFile $file): bool
    {
        foreach ($this->processors as $processor) {
            if ($processor->canProcess($file)) {
                return true;
            }
        }
        return false;
    }

    public function process(UploadedFile $file, array &$metadata): void
    {
        foreach ($this->processors as $processor) {
            if ($processor->canProcess($file)) {
                $processor->process($file, $metadata);
                return;
            }
        }
    }
}
