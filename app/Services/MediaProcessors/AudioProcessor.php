<?php

namespace App\Services\MediaProcessors;

use Illuminate\Http\UploadedFile;
use App\Services\MediaProcessors\MediaProcessorInterface;

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
        // For now, this is a placeholder.
        // Real audio processing (e.g., extracting duration, bitrate,
        // generating waveforms) would require external libraries like
        // FFmpeg or getID3.
        // Example: $metadata['duration'] = $this->getAudioDuration($file);
        // Example: $metadata['bitrate'] = $this->getAudioBitrate($file);

        // You might want to add a generic 'media_type' for categorization
        $metadata['media_type'] = 'audio';
        $metadata['audio_info'] = [
            'mime_type' => $file->getMimeType(),
            'original_name' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
        ];
    }
}
