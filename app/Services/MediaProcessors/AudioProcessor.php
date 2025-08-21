<?php

namespace App\Services\MediaProcessors;

use Illuminate\Http\UploadedFile;
use getID3; // Import the getID3 class

class AudioProcessor implements MediaProcessorInterface
{
    /**
     * Determine if the processor can handle the given file.
     */
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

    /**
     * Process the audio file and extract metadata.
     *
     * @param UploadedFile $file The uploaded audio file.
     * @param array $metadata A reference to the metadata array to populate.
     */
    public function process(UploadedFile $file, array &$metadata): void
    {
        $getID3 = new getID3();
        $fileInfo = $getID3->analyze($file->getPathname());

        // Populate metadata array with relevant audio information
        $metadata['media_type'] = 'audio';
        $metadata['audio_info'] = [
            'mime_type' => $file->getMimeType(),
            'original_name' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
            'format' => $fileInfo['fileformat'] ?? null,
            'encoding' => $fileInfo['encoding'] ?? null,
            'bitrate' => $fileInfo['audio']['bitrate'] ?? null,
            'sample_rate' => $fileInfo['audio']['sample_rate'] ?? null,
            'channels' => $fileInfo['audio']['channels'] ?? null,
            'duration_seconds' => $fileInfo['playtime_seconds'] ?? null,
            'duration_string' => $fileInfo['playtime_string'] ?? null,
            'artist' => $fileInfo['tags']['id3v2']['artist'][0] ?? ($fileInfo['tags']['id3v1']['artist'][0] ?? null),
            'title' => $fileInfo['tags']['id3v2']['title'][0] ?? ($fileInfo['tags']['id3v1']['title'][0] ?? null),
            'album' => $fileInfo['tags']['id3v2']['album'][0] ?? ($fileInfo['tags']['id3v1']['album'][0] ?? null),
            'year' => $fileInfo['tags']['id3v2']['year'][0] ?? ($fileInfo['tags']['id3v1']['year'][0] ?? null),
            'genre' => $fileInfo['tags']['id3v2']['genre'][0] ?? ($fileInfo['tags']['id3v1']['genre'][0] ?? null),
        ];

        // Add duration directly to the top-level metadata for easier access/storage
        $metadata['duration_seconds'] = $fileInfo['playtime_seconds'] ?? null;
        $metadata['duration_string'] = $fileInfo['playtime_string'] ?? null;
    }
}
