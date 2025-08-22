<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AssetService;
use App\Services\ExportService;
use Illuminate\Http\Request;

class ExportController extends Controller
{
    public function __construct(
        private AssetService $assetService,
        private ExportService $exportService
    ) {}

    public function __invoke(Request $request, string $id)
    {
        $asset = $this->assetService->findOne($id, auth()->id());

        $format = $request->query('format', 'json'); // Default to JSON

        return ($this->exportService)($asset, $format);
    }
}
