<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAssetRequest;
use App\Models\Asset;
use App\Services\AssetService;

class AssetVersionController extends Controller
{
    public function __construct(
        private AssetService $assetService
    ) {}

    public function index(string $assetId): \Illuminate\Http\JsonResponse
    {
        $asset = Asset::with('versions')->findOrFail($assetId);
        return response()->json($asset->versions);
    }

    public function show(string $assetId, string $version): \Illuminate\Http\JsonResponse
    {
        $asset = Asset::findOrFail($assetId);
        $assetVersion = $asset->versions()->where('version', $version)->firstOrFail();
        return response()->json($assetVersion);
    }

    public function store(StoreAssetRequest $request, string $assetId): \Illuminate\Http\JsonResponse
    {
        $data = $request->validated();
        $file = $data['file'];
        unset($data['file']);

        $version = $this->assetService->createNewVersion($assetId, $file, auth()->id(), $data);

        return response()->json($version, 201);
    }

    public function destroy(string $assetId, string $version): \Illuminate\Http\JsonResponse
    {
        // TODO: Implement delete version logic
        return response()->json(null, 204);
    }
}
