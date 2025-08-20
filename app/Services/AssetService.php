<?php

namespace App\Services;

use App\Models\Asset;

class AssetService
{
    public function findAll()
    {
        return Asset::all();
    }

    public function findOne(int $id)
    {
        return Asset::findOrFail($id);
    }

    public function create(array $data)
    {
        return Asset::create($data);
    }

    public function update(int $id, array $data)
    {
        $asset = Asset::findOrFail($id);
        $asset->update($data);
        return $asset;
    }

    public function delete(int $id)
    {
        $asset = Asset::findOrFail($id);
        $asset->delete();
        return true;
    }
}
