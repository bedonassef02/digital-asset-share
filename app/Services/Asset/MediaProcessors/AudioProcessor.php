<?php

declare(strict_types=1);

namespace App\Services\MediaProcessors;

use getID3;
use Illuminate\Http\UploadedFile; // Import the getID3 class

class AudioProcessor implements MediaProcessorInterface
{
    public function canProcess(UploadedFile $file): bool
    {
        return in_array($file->getMimeType(), [
            'audio/mpeg', // MP3
            'audio/wav',
            'audio/ogg',
            'audio/aac',
            // Add other audio MIME types as needed
        ]);
    }

    public function process(UploadedFile $file, array &$metadata): void
    {
        $getID3 = new getID3;
        $fileInfo = $getID3->analyze($file->getPathname());

        // Populate metadata array with relevant audio information
        $metadata = [
            'encoding' => $fileInfo['encoding'] ?? null,
            'bitrate' => $fileInfo['audio']['bitrate'] ?? null,
            'sample_rate' => $fileInfo['audio']['sample_rate'] ?? null,
            'channels' => $fileInfo['audio']['channels'] ?? null,
            'duration_seconds' => $fileInfo['playtime_seconds'] ?? null,
            'duration_string' => $fileInfo['playtime_string'] ?? null,
            'artist' => $fileInfo['tags']['id3v2']['artist'][0] ?? ($fileInfo['tags']['id3v1']['artist'][0] ?? null),
            'album' => $fileInfo['tags']['id3v2']['album'][0] ?? ($fileInfo['tags']['id3v1']['album'][0] ?? null),
            'year' => $fileInfo['tags']['id3v2']['year'][0] ?? ($fileInfo['tags']['id3v1']['year'][0] ?? null),
            'genre' => $fileInfo['tags']['id3v2']['genre'][0] ?? ($fileInfo['tags']['id3v1']['genre'][0] ?? null),
        ];
    }
}
