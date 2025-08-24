<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Asset;
use App\Models\AssetVersion;
use App\Services\ServeService;
use App\Services\AssetService;
use App\Services\StorageService;

class ServeServiceTest extends TestCase
{
    protected ServeService $serveService;
    protected $assetServiceMock;
    protected $storageServiceMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assetServiceMock = $this->createMock(AssetService::class);
        $this->storageServiceMock = $this->createMock(StorageService::class);
        $this->serveService = new ServeService($this->assetServiceMock, $this->storageServiceMock);
    }

    /** @test */
    public function it_invokes_asset_service_to_find_one_asset()
    {
        $assetId = 1;
        $userId = 10;
        $mockAsset = $this->createMock(Asset::class);

        $this->assetServiceMock->expects($this->once())
                               ->method('findOne')
                               ->with($assetId, $userId)
                               ->willReturn($mockAsset);

        $result = ($this->serveService)($assetId, $userId);

        $this->assertEquals($mockAsset, $result);
    }

    /** @test */
    public function it_gets_the_asset_path_from_storage_service()
    {
        $asset = Asset::factory()->create();
        $assetVersion = AssetVersion::factory()->create(['asset_id' => $asset->id]);
        $asset->update(['latest_version_id' => $assetVersion->id]);

        $expectedPath = 'path/to/asset/version/file';

        $this->storageServiceMock->expects($this->once())
                                 ->method('getAssetVersionFilePath')
                                 ->with($asset->id, $assetVersion->version, 'file') // 'file' is the default filename
                                 ->willReturn($expectedPath);

        $result = $this->serveService->getAssetPath($asset);

        $this->assertEquals($expectedPath, $result);
    }
}
