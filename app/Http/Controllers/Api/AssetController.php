<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BulkDeleteAssetsRequest;
use App\Http\Requests\BulkDownloadAssetsRequest;
use App\Http\Requests\BulkTagAssetsRequest;
use App\Http\Requests\ChangeAssetStatusRequest;
use App\Http\Requests\StoreAssetRequest;
use App\Http\Requests\UpdateAssetRequest;
use App\Http\Resources\AssetResource;
use App\Services\AssetService;
use App\Services\DownloadService;
use App\Services\TagService;
use App\Services\ViewService;
use Illuminate\Http\Request;

class AssetController extends Controller
{
    public function __construct(
        private AssetService $assetService,
        private ViewService $viewService,
        private TagService $tagService,
        private DownloadService $downloadService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 15);
        $assets = $this->assetService->findAll(auth()->id(), $perPage);
        return AssetResource::collection($assets);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAssetRequest $request)
    {
        $data = $request->validated();
        $file = $data['file'];
        unset($data['file']);
        $data['user_id'] = auth()->id(); // Assign the authenticated user's ID
        $asset = $this->assetService->create($file, $data);
        return new AssetResource($asset);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $userId = auth()->id();
        $asset = $this->assetService->findOne($id, $userId);

        // Record the asset view
        if (auth()->check()) {
            $this->viewService->record(auth()->user(), $asset);
        }

        return new AssetResource($asset);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAssetRequest $request, string $id)
    {
        $asset = $this->assetService->update($id, $request->validated());
        return new AssetResource($asset);
    }

    /**
     * Remove the specified resource from storage (soft or hard delete).
     */
    public function destroy(Request $request, string $id)
    {
        if ($request->query('force')) {
            $this->assetService->forceDelete($id);
        } else {
            $this->assetService->softDelete($id);
        }
        return response()->json(null, 204);
    }

    public function bulkDestroy(BulkDeleteAssetsRequest $request): \Illuminate\Http\JsonResponse
    {
        $assetIds = $request->validated('asset_ids');
        $this->assetService->bulkSoftDelete($assetIds);
        return response()->json(null, 204);
    }

    public function bulkTag(BulkTagAssetsRequest $request): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validated();
        $assetIds = $validated['asset_ids'];
        $tags = $validated['tags'];
        $this->tagService->bulkTag($assetIds, $tags);
        return response()->json(null, 204);
    }

    public function changeStatus(ChangeAssetStatusRequest $request, string $id): \Illuminate\Http\JsonResponse
    {
        $this->assetService->changeStatus($id, $request->validated('status'));
        return response()->json(null, 204);
    }

    public function restore(string $id): \Illuminate\Http\JsonResponse
    {
        $this->assetService->restore($id);
        return response()->json(null, 204);
    }

    public function bulkDownload(BulkDownloadAssetsRequest $request)
    {
        $validated = $request->validated();
        $assetIds = $validated['asset_ids'] ?? [];
        $collectionIds = $validated['collection_ids'] ?? [];

        try {
            $zipFilePath = $this->downloadService->createBulkDownloadZip($assetIds, $collectionIds);

            return response()->download($zipFilePath)->deleteFileAfterSend(true);
        } catch (ZipCreationException $e) {
            Log::error('Bulk download zip creation failed: ' . $e->getMessage());
            return response()->json(['message' => 'Could not create download package. Please try again later.'], 500);
        } catch (\Exception $e) {
            Log::critical('An unexpected error occurred during bulk download: ' . $e->getMessage());
            return response()->json(['message' => 'An unexpected error occurred.'], 500);
        }
    }
}