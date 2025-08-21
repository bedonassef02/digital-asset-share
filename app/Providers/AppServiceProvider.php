<?php

namespace App\Providers;

use App\Services\MediaService;
use App\Services\MediaProcessors\ImageProcessor;
use App\Services\MediaProcessors\PdfProcessor;
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
                $app->make(PdfProcessor::class)
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
