<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DownloadService;
use App\Services\ServeService;
use Illuminate\Support\Facades\Storage;

class ServeController extends Controller
{
    public function __construct(
        private ServeService $serveService,
    ) {}

    public function __invoke(int $id): \Illuminate\Http\JsonResponse|\Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $result = $this->serveService->serveAsset($id, auth()->id(), auth()->user());

        $asset = $result['asset'];
        $path = $result['path'];

        return response()->file(Storage::disk()->path($path), ['Content-Disposition' => 'inline; filename="'.$asset->latestVersion->name.'"']);
    }
}
