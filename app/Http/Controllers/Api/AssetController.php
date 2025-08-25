<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BulkDeleteAssetsRequest;
use App\Http\Requests\BulkTagAssetsRequest;
use App\Http\Requests\ChangeAssetStatusRequest;
use App\Http\Requests\StoreAssetRequest;
use App\Http\Requests\UpdateAssetRequest;
use App\Http\Resources\AssetResource;
use App\Services\AssetService;
use App\Services\TagService;
use Illuminate\Http\Request;

class AssetController extends Controller
{
    public function __construct(
        private AssetService $assetService,
        private TagService $tagService
    ) {}

    public function index(Request $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $perPage = (int) $request->query('per_page', 15);
        $assets = $this->assetService->findAll(auth()->id(), $perPage);

        return AssetResource::collection($assets);
    }

    public function store(StoreAssetRequest $request): AssetResource
    {
        $data = $request->validated();
        $file = $data['file'];
        unset($data['file']);
        $data['user_id'] = auth()->id(); // Assign the authenticated user's ID
        $asset = $this->assetService->create($file, $data);

        return new AssetResource($asset);
    }

    public function show(int $id): AssetResource
    {
        $asset = $this->assetService->findOne($id, auth()->id());

        return new AssetResource($asset);
    }

    public function update(UpdateAssetRequest $request, int $id): AssetResource
    {
        $asset = $this->assetService->update($id, $request->validated(), auth()->id());

        return new AssetResource($asset);
    }

    public function destroy(Request $request, int $id): \Illuminate\Http\JsonResponse
    {
        $this->assetService->delete($id, auth()->id(), (bool) $request->query('force', false));

        return response()->json(null, 204);
    }

    public function bulkDestroy(BulkDeleteAssetsRequest $request): \Illuminate\Http\JsonResponse
    {
        $assetIds = $request->validated('asset_ids');
        $this->assetService->bulkSoftDelete($assetIds, auth()->id());

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

    public function changeStatus(ChangeAssetStatusRequest $request, int $id): \Illuminate\Http\JsonResponse
    {
        $this->assetService->changeStatus($id, $request->validated('status'), auth()->id());

        return response()->json(null, 204);
    }

    public function restore(int $id): \Illuminate\Http\JsonResponse
    {
        $this->assetService->restore($id, auth()->id());

        return response()->json(null, 204);
    }
}
