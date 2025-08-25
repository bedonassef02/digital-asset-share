<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAssetRequest;
use App\Services\AssetVersionService;

class AssetVersionController extends Controller
{
    public function __construct(
        private AssetVersionService $assetVersionService
    ) {}

    public function index(int $assetId): \Illuminate\Http\JsonResponse
    {
        $versions = $this->assetVersionService->getVersionsForAsset($assetId);

        return response()->json($versions);
    }

    public function show(int $assetId, string $version): \Illuminate\Http\JsonResponse
    {
        $assetVersion = $this->assetVersionService->getSpecificVersion($assetId, $version);

        return response()->json($assetVersion);
    }

    public function store(StoreAssetRequest $request, int $assetId): \Illuminate\Http\JsonResponse
    {
        $data = $request->validated();
        $file = $data['file'];
        unset($data['file']);

        $version = $this->assetVersionService->createNewVersion($assetId, $file, auth()->id(), $data);

        return response()->json($version, 201);
    }

    public function destroy(int $assetId, int $version): \Illuminate\Http\JsonResponse
    {
        try {
            $this->assetVersionService->deleteVersion($assetId, $version);

            return response()->json(null, 204);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}
