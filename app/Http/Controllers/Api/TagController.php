<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TagRequest;
use App\Services\TagService;

class TagController extends Controller
{
    public function __construct(
        private TagService $tagService
    ) {}

    public function add(TagRequest $request, int $id): \Illuminate\Http\JsonResponse
    {
        $this->tagService->add($id, $request->input('tag'));

        return response()->json(null, 204);
    }

    public function remove(TagRequest $request, int $id): \Illuminate\Http\JsonResponse
    {
        $this->tagService->remove($id, $request->input('tag'));

        return response()->json(null, 204);
    }
}
