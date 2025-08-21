<?php

namespace App\Services\MediaProcessors;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use FFMpeg\FFMpeg;
use FFMpeg\FFProbe;

class VideoProcessor implements MediaProcessorInterface
{
    protected FFMpeg $ffmpeg;
    protected FFProbe $ffprobe;

    public function __construct(FFMpeg $ffmpeg, FFProbe $ffprobe)
    {
        $this->ffmpeg = $ffmpeg;
        $this->ffprobe = $ffprobe;
    }

    public function canProcess(UploadedFile $file): bool
    {
        return str_starts_with($file->getMimeType(), 'video/');
    }

    public function process(UploadedFile $file, array &$metadata): void
    {
        try {
            $videoInfo = $this->ffprobe->streams($file->getRealPath())->videos()->first();

            if ($videoInfo) {
                $metadata['video_duration'] = $videoInfo->get('duration'); // in seconds
                $metadata['video_width'] = $videoInfo->get('width');
                $metadata['video_height'] = $videoInfo->get('height');
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
