<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Services\ShareService;
use App\Http\Requests\StoreShareRequest;
use App\Http\Requests\ResolveShareRequest;
use Illuminate\Support\Facades\Auth;

class ShareController extends Controller
{
    public function __construct(
        private ShareService $shareService
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

        return response()->json($asset);
    }

    public function revoke(string $token)
    {
        $this->shareService->revoke($token);

        return response()->json(null, 204);
    }
}
