<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ShareService;
use App\Http\Requests\ResolveShareRequest;
use App\Services\ViewService;

class ShareController extends Controller
{
    public function __construct(
        private ShareService $shareService,
        private ViewService $viewService
    ) {}

    public function create(StoreShareRequest $request, Asset $asset)
    {
        $share = $this->shareService->create($asset, $request->all());

        return response()->json($share, 201);
    }

    public function list()
    {
        $shares = $this->shareService->list();

        return response()->json($shares);
    }

    public function resolve(ResolveShareRequest $request, string $token)
    {
        $asset = $this->shareService->resolve($token, $request->input('password'));

        $this->assetViewService->record(auth()->user(), $asset);

        return response()->json($asset);
    }

    public function revoke(string $token)
    {
        $this->shareService->revoke($token);

        return response()->json(null, 204);
    }
}
