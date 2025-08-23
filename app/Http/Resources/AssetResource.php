<?php

namespace App\Http\Resources;

use App\Services\AssetService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssetResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $assetService = app(AssetService::class);

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'latest_version' => $this->whenLoaded('latestVersion'),
            'metadata' => $assetService->getMetadata($this->resource),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}