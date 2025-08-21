<?php

namespace App\Providers;

use App\Services\MediaService;
use App\Services\MediaProcessors\ImageProcessor;
use App\Services\MediaProcessors\PdfProcessor;
use App\Services\MediaProcessors\AudioProcessor;
use App\Services\MediaProcessors\TextProcessor;
use App\Services\MediaProcessors\ArchiveProcessor;
use App\Services\MediaProcessors\VideoProcessor;
use App\Services\MediaProcessors\OfficeProcessor;
use App\Services\MediaProcessors\Office\ExcelProcessor;
use App\Services\MediaProcessors\Office\PowerPointProcessor;
use App\Services\MediaProcessors\Office\WordDocumentProcessor;
use FFMpeg\FFMpeg;
use FFMpeg\FFProbe;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(FFMpeg::class, function ($app) {
            return FFMpeg::create([
                'ffmpeg.binaries'  => config('ffmpeg.ffmpeg_binary_path'),
                'ffprobe.binaries' => config('ffmpeg.ffprobe_binary_path'),
                'timeout'          => 3600,
                'ffmpeg.threads'   => 12,
            ]);
        });

        $this->app->singleton(FFProbe::class, function ($app) {
            return FFProbe::create([
                'ffmpeg.binaries'  => config('ffmpeg.ffmpeg_binary_path'),
                'ffprobe.binaries' => config('ffmpeg.ffprobe_binary_path'),
                'timeout'          => 3600,
                'ffmpeg.threads'   => 12,
            ]);
        });

        $this->app->singleton(MediaService::class, function ($app) {
            return new MediaService(
                $app->make(ImageProcessor::class),
                $app->make(PdfProcessor::class),
                $app->make(ArchiveProcessor::class),
                $app->make(AudioProcessor::class),
                $app->make(TextProcessor::class),
                $app->make(VideoProcessor::class),
                $app->make(OfficeProcessor::class, [
                    $app->make(ExcelProcessor::class),
                    $app->make(PowerPointProcessor::class),
                    $app->make(WordDocumentProcessor::class),
                ])
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
