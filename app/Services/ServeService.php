<?php

namespace App\Services;

class ServeService
{
    public function __construct(
        private AssetService $assetService
    ) {}

    public function __invoke(string $id)
    {
        return $this->assetService->findOne($id);
    }
}
