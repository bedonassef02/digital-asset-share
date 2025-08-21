<?php

namespace App\Services\MediaProcessors;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use FFMpeg\FFMpeg;
use FFMpeg\FFProbe;

class VideoProcessor implements MediaProcessorInterface
{
    public function canProcess(UploadedFile $file): bool
    {
        return str_starts_with($file->getMimeType(), 'video/');
    }

    public function process(UploadedFile $file, array &$metadata): void
    {
        try {
            // Ensure ffmpeg and ffprobe binaries are accessible
            // You might need to configure the paths if they are not in your system's PATH
            $ffmpeg = FFMpeg::create([
                'ffmpeg.binaries'  => '/usr/bin/ffmpeg', // Adjust path as needed for your system
                'ffprobe.binaries' => '/usr/bin/ffprobe', // Adjust path as needed for your system
                'timeout'          => 3600, // The timeout for the underlying process
                'ffmpeg.threads'   => 12,   // The number of threads that FFMpeg should use
            ]);

            $ffprobe = FFProbe::create([
                'ffmpeg.binaries'  => '/usr/bin/ffmpeg', // Adjust path as needed for your system
                'ffprobe.binaries' => '/usr/bin/ffprobe', // Adjust path as needed for your system
                'timeout'          => 3600, // The timeout for the underlying process
                'ffmpeg.threads'   => 12,   // The number of threads that FFMpeg should use
            ]);

            $video = $ffmpeg->open($file->getRealPath());
            $videoInfo = $ffprobe->streams($file->getRealPath())->videos()->first();

            if ($videoInfo) {
                $metadata['video_duration'] = $videoInfo->getDuration(); // in seconds
                $metadata['video_width'] = $videoInfo->getDimensions()->getWidth();
                $metadata['video_height'] = $videoInfo->getDimensions()->getHeight();
                $metadata['video_codec'] = $videoInfo->get('codec_name');
                $metadata['video_bitrate'] = $videoInfo->get('bit_rate'); // in bits per second
                $metadata['video_frame_rate'] = $videoInfo->get('avg_frame_rate');
            }

            $metadata['video_mime_type'] = $file->getMimeType();
            $metadata['video_size'] = $file->getSize(); // in bytes

        } catch (\Exception $e) {
            Log::warning('Could not process video file: ' . $e->getMessage());
        }
    }
}
