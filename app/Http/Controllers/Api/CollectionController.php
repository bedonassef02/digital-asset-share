<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCollectionRequest;
use App\Http\Requests\UpdateCollectionRequest;
use App\Http\Requests\UpdateCollectionAssetsRequest;
use App\Services\CollectionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CollectionController extends Controller
{
    public function __construct(private CollectionService $collectionService) { }

    public function index(Request $request): JsonResponse
    {
        $userId = auth()->id();
        $perPage = $request->query('per_page', 15);
        $collections = $this->collectionService->findAll($userId, $perPage);
        return response()->json($collections);
    }

    public function store(StoreCollectionRequest $request): JsonResponse
    {

        $data = $request->validated();
        $data['user_id'] = auth()->id();

        $collection = $this->collectionService->create($data);
        return response()->json($collection, 201);
    }

    public function show(int $id): JsonResponse
    {
        $userId = auth()->id();
        $collection = $this->collectionService->find($id, $userId);
        return response()->json($collection);
    }

    public function update(UpdateCollectionRequest $request, int $id): JsonResponse
    {

        $collection = $this->collectionService->update($id, $request->validated(), auth()->id());
        return response()->json($collection);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->collectionService->delete($id, auth()->id());
        return response()->json(null, 204);
    }

    public function addAssets(UpdateCollectionAssetsRequest $request, int $id): JsonResponse
    {
        $collection = $this->collectionService->addAssets($id, $request->input('asset_ids'), auth()->id());
        return response()->json($collection);
    }

    public function removeAssets(UpdateCollectionAssetsRequest $request, int $id): JsonResponse
    {
        $collection = $this->collectionService->removeAssets($id, $request->input('asset_ids'), auth()->id());
        return response()->json($collection);
    }

    public function getCollectionAssets(Request $request, int $id): JsonResponse
    {
        $perPage = $request->query('per_page', 15);
        $assets = $this->collectionService->getCollectionAssets($id, $perPage, auth()->id());
        return response()->json($assets);
    }

    public function getRootCollections(Request $request): JsonResponse
    {
        $userId = auth()->id();
        $collections = $this->collectionService->getRootCollections($userId);
        return response()->json($collections);
    }

    public function getChildCollections(Request $request, int $parentId): JsonResponse
    {
        $collections = $this->collectionService->getChildCollections($parentId, auth()->id());
        return response()->json($collections);
    }
}
