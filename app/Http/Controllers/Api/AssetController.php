<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAssetRequest;
use App\Http\Requests\UpdateAssetRequest;
use App\Services\AssetService;
use Illuminate\Http\Request;

class AssetController extends Controller
{
    public function __construct(
        private AssetService $assetService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $data = $request->validated();
        $file = $data['file'];
        unset($data['file']);
        $data['user_id'] = auth()->id(); // Assign the authenticated user's ID
        $asset = $this->assetService->create($file, $data);
        return response()->json($asset, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $userId = auth()->id();
        $asset = $this->assetService->findOne($id, $userId);
        return response()->json($asset);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAssetRequest $request, string $id)
    {
        $asset = $this->assetService->update($id, $request->validated());
        return response()->json($asset);
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
}
