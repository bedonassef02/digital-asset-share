<?php

declare(strict_types=1);

namespace App\Services\MediaProcessors;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use ZipArchive;

class ArchiveProcessor implements MediaProcessorInterface
{
    public function canProcess(UploadedFile $file): bool
    {
        $mimeType = $file->getMimeType();

        return in_array($mimeType, [
            'application/zip',
            'application/x-rar-compressed',
            'application/x-tar',
            'application/gzip',
            'application/x-7z-compressed',
        ]);
    }

    public function process(UploadedFile $file, array &$metadata): void
    {
        $mimeType = $file->getMimeType();
        $filePath = $file->getRealPath();

        try {
            if ($mimeType === 'application/zip') {
                $zip = new ZipArchive;
                if ($zip->open($filePath) === true) {
                    $metadata['archive_type'] = 'zip';
                    $metadata['file_count'] = $zip->numFiles;
                    $fileList = [];
                    for ($i = 0; $i < $zip->numFiles; $i++) {
                        $fileList[] = $zip->getNameIndex($i);
                    }
                    $metadata['contained_files'] = $fileList;
                    $zip->close();
                } else {
                    Log::warning('Could not open zip archive: '.$filePath);
                }
            } else {
                $metadata['archive_type'] = explode('/', $mimeType)[1] ?? $mimeType;
                Log::info(sprintf('%s archive detected, but detailed processing not implemented.', $metadata['archive_type']));
            }
        } catch (\Exception $e) {
            Log::error('Error processing archive file: '.$e->getMessage());
        }
    }
}
