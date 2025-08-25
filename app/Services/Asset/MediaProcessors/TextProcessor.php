<?php

declare(strict_types=1);

namespace App\Services\MediaProcessors;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class TextProcessor implements MediaProcessorInterface
{
    public function canProcess(UploadedFile $file): bool
    {
        return str_starts_with($file->getMimeType(), 'text/') ||
               $file->getMimeType() === 'application/json' ||
               $file->getMimeType() === 'application/xml';
    }

    public function process(UploadedFile $file, array &$metadata): void
    {
        try {
            $content = file_get_contents($file->getRealPath());

            $metadata['text_line_count'] = count(explode("\n", $content));
            $metadata['text_word_count'] = str_word_count($content);
            $metadata['text_character_count'] = strlen($content);
            $metadata['text_preview'] = substr($content, 0, 500); // First 500 characters as preview
            $metadata['text_encoding'] = mb_detect_encoding($content, mb_detect_order(), true);

        } catch (\Exception $e) {
            Log::warning('Could not process text file: '.$e->getMessage());
        }
    }
}
