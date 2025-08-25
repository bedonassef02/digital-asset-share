<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SearchAssetsRequest;
use App\Services\SearchService;

class SearchController extends Controller
{
    public function __construct(
        private SearchService $searchService
    ) {}

    public function search(SearchAssetsRequest $request): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validated();

        $results = $this->searchService->search(
            $validated['q'],
            auth()->id(),
            $validated['mime_type'] ?? null,
            $validated['min_size'] ?? null,
            $validated['max_size'] ?? null,
            $validated['start_date'] ?? null,
            $validated['end_date'] ?? null
        );

        return response()->json($results);
    }
}
