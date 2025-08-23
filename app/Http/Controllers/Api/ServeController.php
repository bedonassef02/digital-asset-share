<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ServeService;
use Illuminate\Support\Facades\Storage;

class ServeController extends Controller
{
    public function __construct(
        private ServeService $serveService
    ) {}

    /**
     * Serve the specified asset file.
     */
    public function __invoke(string $id)
    {
        $asset = ($this->serveService)($id);

        if (!$asset) {
            return response()->json(['message' => 'Asset not found'], 404);
        }

        $path = $this->serveService->getAssetPath($asset);

        return response()->file(Storage::disk()->path($path), ['Content-Disposition' => 'inline; filename="' . $asset->latestVersion->name . '"']);
    }
}
