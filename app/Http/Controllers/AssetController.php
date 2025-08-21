<?php

namespace App\Http\Controllers;

use App\Services\AssetService;
use App\Http\Requests\StoreAssetRequest;
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
        $perPage = $request->query('per_page', 15);
        $assets = $this->assetService->findAll(auth()->id, $perPage);
        return response()->json($assets);
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
        return response()->json($asset, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $asset = $this->assetService->findOne($id);
        return response()->json($asset);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $asset = $this->assetService->update($id, $request->all());
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
