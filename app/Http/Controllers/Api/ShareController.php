<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ResolveShareRequest;
use App\Http\Requests\StoreShareRequest;
use App\Models\Asset;
use App\Models\Collection;
use App\Services\ShareService;

class ShareController extends Controller
{
    public function __construct(
        private ShareService $shareService,
    ) {}

    public function shareAsset(StoreShareRequest $request, Asset $asset): \Illuminate\Http\JsonResponse
    {
        $share = $this->shareService->create($asset, $request->validated(), auth()->id());

        return response()->json($share, 201);
    }

    public function shareCollection(StoreShareRequest $request, Collection $collection): \Illuminate\Http\JsonResponse
    {
        $share = $this->shareService->create($collection, $request->validated(), auth()->id());

        return response()->json($share, 201);
    }

    public function list(): \Illuminate\Http\JsonResponse
    {
        $shares = $this->shareService->list(auth()->id());

        return response()->json($shares);
    }

    public function resolve(ResolveShareRequest $request, string $token): \Illuminate\Http\JsonResponse
    {
        $shareable = $this->shareService->resolve($token, auth()->id(), $request->input('password'));

        return response()->json($shareable);
    }

    public function revoke(string $token): \Illuminate\Http\JsonResponse
    {
        $this->shareService->revoke($token, auth()->id());

        return response()->json(null, 204);
    }

    public function stats(string $token): \Illuminate\Http\JsonResponse
    {
        $stats = $this->shareService->getStats($token);

        return response()->json($stats);
    }
}
