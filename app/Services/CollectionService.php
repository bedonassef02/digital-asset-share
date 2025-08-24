<?php

namespace App\Services;

use App\Models\Collection;
use App\Models\Asset;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class CollectionService
{
    public function create(array $data): Collection
    {
        return Collection::create($data);
    }

    public function update(int $id, array $data): Collection
    {
        $collection = Collection::findOrFail($id);
        $collection->update($data);
        return $collection;
    }

    public function delete(int $id): bool
    {
        $collection = Collection::findOrFail($id);
        return $collection->delete();
    }

    public function find(int $id): Collection
    {
        return Collection::findOrFail($id);
    }

    public function findAll(int $userId, int $perPage = 15)
    {
        return Collection::where('user_id', $userId)->paginate($perPage);
    }

    public function addAssets(int $collectionId, array $assetIds): Collection
    {
        $collection = Collection::findOrFail($collectionId);
        $collection->assets()->syncWithoutDetaching($assetIds);
        return $collection;
    }

    public function removeAssets(int $collectionId, array $assetIds): Collection
    {
        $collection = Collection::findOrFail($collectionId);
        $collection->assets()->detach($assetIds);
        return $collection;
    }

    public function getCollectionAssets(int $collectionId, int $perPage = 15)
    {
        $collection = Collection::with('assets.latestVersion')->findOrFail($collectionId);
        return $collection->assets()->paginate($perPage);
    }

    public function getRootCollections(int $userId)
    {
        return Collection::where('user_id', $userId)->whereNull('parent_id')->get();
    }

    public function getChildCollections(int $parentId)
    {
        return Collection::where('parent_id', $parentId)->get();
    }
}
