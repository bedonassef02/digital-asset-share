<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Asset;
use App\Models\Collection;
use App\Models\Share;
use App\Policies\AssetPolicy;
use App\Policies\CollectionPolicy;
use App\Policies\SharePolicy;
use App\Services\Asset\MediaProcessors\ArchiveProcessor;
use App\Services\Asset\MediaProcessors\AudioProcessor;
use App\Services\Asset\MediaProcessors\ImageProcessor;
use App\Services\Asset\MediaProcessors\Office\ExcelProcessor;
use App\Services\Asset\MediaProcessors\Office\PowerPointProcessor;
use App\Services\Asset\MediaProcessors\Office\WordDocumentProcessor;
use App\Services\Asset\MediaProcessors\OfficeProcessor;
use App\Services\Asset\MediaProcessors\PdfProcessor;
use App\Services\Asset\MediaProcessors\TextProcessor;
use App\Services\Asset\MediaProcessors\VideoProcessor;
use App\Services\Asset\MediaService;
use FFMpeg\FFMpeg;
use FFMpeg\FFProbe;
use Illuminate\Support\Facades\Gate;
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
                'ffmpeg.binaries' => config('ffmpeg.ffmpeg_binary_path'),
                'ffprobe.binaries' => config('ffmpeg.ffprobe_binary_path'),
                'timeout' => 3600,
                'ffmpeg.threads' => 12,
            ]);
        });

        $this->app->singleton(FFProbe::class, function ($app) {
            return FFProbe::create([
                'ffmpeg.binaries' => config('ffmpeg.ffmpeg_binary_path'),
                'ffprobe.binaries' => config('ffmpeg.ffprobe_binary_path'),
                'timeout' => 3600,
                'ffmpeg.threads' => 12,
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
        Gate::policy(Asset::class, AssetPolicy::class);
        Gate::policy(Collection::class, CollectionPolicy::class);
        Gate::policy(Share::class, SharePolicy::class);
    }
}
