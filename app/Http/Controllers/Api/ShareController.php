<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreShareRequest;
use App\Models\Asset;
use App\Models\Collection;
use App\Services\ShareService;
use App\Http\Requests\ResolveShareRequest;
use App\Services\ViewService;

class ShareController extends Controller
{
    public function __construct(
        private ShareService $shareService,
        private ViewService $viewService
    ) {}

    public function shareAsset(StoreShareRequest $request, Asset $asset)
    {
        $this->authorize('share', $asset);
        $share = $this->shareService->create($asset, $request->validated(), auth()->id());

        return response()->json($share, 201);
    }

    public function shareCollection(StoreShareRequest $request, Collection $collection)
    {
        $this->authorize('share', $collection);
        $share = $this->shareService->create($collection, $request->validated(), auth()->id());

        return response()->json($share, 201);
    }

    public function list()
    {
        $shares = $this->shareService->list(auth()->id());

        return response()->json($shares);
    }

    public function resolve(ResolveShareRequest $request, string $token)
    {
        $shareable = $this->shareService->resolve($token, $request->input('password'));

        if ($shareable instanceof Asset) {
            $this->viewService->record(auth()->user(), $shareable);
        }

        return response()->json($shareable);
    }

    public function revoke(string $token)
    {
        $this->shareService->revoke($token, auth()->id());

        return response()->json(null, 204);
    }
}
