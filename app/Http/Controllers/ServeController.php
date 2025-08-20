<?php

namespace App\Http\Controllers;

use App\Services\AssetService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ServeController extends Controller
{
    public function __construct(
        private AssetService $assetService
    ) {}

    /**
     * Serve the specified asset file.
     */
    public function __invoke(Request $request)
    {
        $asset = $this->assetService->findOne($request->query('id'));

        if (!$asset) {
            return response()->json(['message' => 'Asset not found'], 404);
        }

        return response()->file(Storage::disk($asset->disk)->path($asset->path), ['Content-Disposition' => 'inline; filename="' . $asset->file_name . '"]']);
    }
}
