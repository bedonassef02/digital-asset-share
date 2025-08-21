<?php

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
            switch ($mimeType) {
                case 'application/zip':
                    $zip = new ZipArchive();
                    if ($zip->open($filePath) === TRUE) {
                        $metadata['archive_type'] = 'zip';
                        $metadata['file_count'] = $zip->numFiles;
                        $fileList = [];
                        for ($i = 0; $i < $zip->numFiles; $i++) {
                            $fileList[] = $zip->getNameIndex($i);
                        }
                        $metadata['contained_files'] = $fileList;
                        $zip->close();
                    } else {
                        Log::warning('Could not open zip archive: ' . $filePath);
                    }
                    break;
                case 'application/x-rar-compressed':
                    $metadata['archive_type'] = 'rar';
                    Log::info('RAR archive detected, but detailed processing not implemented.');
                    // RAR processing typically requires external libraries (e.g., unrar)
                    break;
                case 'application/x-tar':
                    $metadata['archive_type'] = 'tar';
                    Log::info('TAR archive detected, but detailed processing not implemented.');
                    // TAR processing can be done with PharData, but might be complex for nested archives
                    break;
                case 'application/gzip':
                    $metadata['archive_type'] = 'gzip';
                    Log::info('GZIP archive detected, but detailed processing not implemented.');
                    // GZIP is a single file compression, not an archive of multiple files
                    break;
                case 'application/x-7z-compressed':
                    $metadata['archive_type'] = '7z';
                    Log::info('7z archive detected, but detailed processing not implemented.');
                    // 7z processing typically requires external libraries
                    break;
                default:
                    Log::warning('Unhandled archive MIME type: ' . $mimeType);
                    break;
            }
        } catch (\Exception $e) {
            Log::error('Error processing archive file: ' . $e->getMessage());
        }
    }
}
