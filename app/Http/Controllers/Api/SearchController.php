<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SearchService;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __construct(
        private SearchService $searchService
    ) {}

    public function search(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:1',
        ]);

        $results = $this->searchService->search($request->input('q'));

        return response()->json($results);
    }
}
