<?php

namespace App\Providers;

use App\Services\MediaService;
use App\Services\MediaProcessors\ImageProcessor;
use App\Services\MediaProcessors\PdfProcessor;
use App\Services\MediaProcessors\WordDocumentProcessor;
use App\Services\MediaProcessors\ExcelProcessor;
use App\Services\MediaProcessors\PowerPointProcessor;
use App\Services\MediaProcessors\AudioProcessor;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(MediaService::class, function ($app) {
            return new MediaService(
                $app->make(ImageProcessor::class),
                $app->make(PdfProcessor::class),
                $app->make(WordDocumentProcessor::class),
                $app->make(ExcelProcessor::class),
                $app->make(PowerPointProcessor::class),
                $app->make(AudioProcessor::class)
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
