<?php

namespace App\Services;

use App\Models\Collection;
use App\Models\Asset;

class CollectionService
{
    public function create(array $data): Collection
    {
        return Collection::create($data);
    }

    public function update(int $id, array $data, int $userId): Collection
    {
        $collection = Collection::where('id', $id)->where('user_id', $userId)->firstOrFail();
        $collection->update($data);
        return $collection;
    }

    public function delete(int $id, int $userId): bool
    {
        $collection = Collection::where('id', $id)->where('user_id', $userId)->firstOrFail();
        return $collection->delete();
    }

    public function find(int $id, int $userId): Collection
    {
        return Collection::where('id', $id)->where('user_id', $userId)->firstOrFail();
    }

    public function findAll(int $userId, int $perPage = 15)
    {
        return Collection::where('user_id', $userId)->paginate($perPage);
    }

    public function addAssets(int $collectionId, array $assetIds, int $userId): Collection
    {
        $collection = $this->find($collectionId, $userId);
        $this->verifyAssetsBelongToUser($assetIds, $userId);

        $collection->assets()->syncWithoutDetaching($assetIds);
        return $collection;
    }

    public function removeAssets(int $collectionId, array $assetIds, int $userId): Collection
    {
        $collection = $this->find($collectionId, $userId);
        $this->verifyAssetsBelongToUser($assetIds, $userId);

        $collection->assets()->detach($assetIds);
        return $collection;
    }

    private function verifyAssetsBelongToUser(array $assetIds, int $userId): void
    {
        $userAssetsCount = Asset::whereIn('id', $assetIds)->where('user_id', $userId)->count();
        if ($userAssetsCount !== count($assetIds)) {
            throw new \Exception('One or more assets do not belong to the authenticated user.');
        }
    }

    public function getCollectionAssets(int $collectionId, int $userId, int $perPage = 15)
    {
        $collection = Collection::with(['assets' => function($query) {
            $query->whereNull('deleted_at')->with('latestVersion');
        }])->where('id', $collectionId)->where('user_id', $userId)->firstOrFail();

        return $collection->assets()->paginate($perPage);
    }

    public function getRootCollections(int $userId)
    {
        return Collection::where('user_id', $userId)->whereNull('parent_id')->get();
    }

    public function getChildCollections(int $parentId, int $userId)
    {
        // Ensure the parent collection belongs to the user
        Collection::where('id', $parentId)->where('user_id', $userId)->firstOrFail();

        return Collection::where('parent_id', $parentId)->where('user_id', $userId)->get();
    }
}
