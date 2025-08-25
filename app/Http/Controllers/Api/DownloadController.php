<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Exceptions\ZipCreationException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Asset\BulkDownloadAssetsRequest;
use App\Services\Download\DownloadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DownloadController extends Controller
{
    public function __construct(
        private DownloadService $downloadService
    ) {}

    public function bulkDownload(BulkDownloadAssetsRequest $request): BinaryFileResponse|JsonResponse
    {
        $validated = $request->validated();
        $assetIds = $validated['asset_ids'] ?? [];
        $collectionIds = $validated['collection_ids'] ?? [];

        try {
            $zipFilePath = $this->downloadService->createBulkDownloadZip($assetIds, $collectionIds);

            return response()->download($zipFilePath)->deleteFileAfterSend(true);
        } catch (ZipCreationException $e) {
            Log::error('Bulk download zip creation failed: '.$e->getMessage());

            return response()->json(['message' => 'Could not create download package. Please try again later.'], 500);
        } catch (\Exception $e) {
            Log::critical('An unexpected error occurred during bulk download: '.$e->getMessage());

            return response()->json(['message' => 'An unexpected error occurred.'], 500);
        }
    }
}
