<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Services\ShareService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ShareController extends Controller
{
    protected $shareService;

    public function __construct(ShareService $shareService)
    {
        $this->shareService = $shareService;
    }

    public function create(Request $request, Asset $asset)
    {
        $request->validate([
            'expires_at' => 'nullable|date',
            'password' => 'nullable|string|min:6',
        ]);

        $share = $this->shareService->createShareLink($asset, $request->all());

        return response()->json($share, 201);
    }

    public function list()
    {
        $shares = $this->shareService->listUserShares();

        return response()->json($shares);
    }

    public function resolve(Request $request, string $token)
    {
        $request->validate([
            'password' => 'nullable|string',
        ]);

        $asset = $this->shareService->resolveShareLink($token, $request->input('password'));

        return response()->json($asset);
    }

    public function revoke(string $token)
    {
        $this->shareService->revokeShareLink($token);

        return response()->json(null, 204);
    }
}
