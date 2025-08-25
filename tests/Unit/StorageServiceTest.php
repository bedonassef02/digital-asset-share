<?php

namespace Tests\Unit;

use App\Services\PathService;
use App\Services\StorageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class StorageServiceTest extends TestCase
{
    protected StorageService $storageService;

    protected $pathServiceMock;

    protected $mockDisk;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pathServiceMock = $this->createMock(PathService::class);

        $this->storageService = new StorageService($this->pathServiceMock);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_stores_a_file_correctly()
    {
        $assetId = 1;
        $version = 1;
        $assetDirectory = 'uploads/0/1/1';
        $fileName = 'test.jpg';

        $file = UploadedFile::fake()->image($fileName);

        $this->pathServiceMock->expects($this->once())
            ->method('getAssetVersionPath')
            ->with($assetId, $version)
            ->willReturn($assetDirectory);

        Storage::shouldReceive('disk')->andReturnSelf();
        Storage::shouldReceive('makeDirectory')
            ->once()
            ->with($assetDirectory)
            ->andReturn(true);

        Storage::shouldReceive('putFileAs')
            ->once()
            ->with($assetDirectory, $file, PathService::DEFAULT_FILENAME)
            ->andReturn('path/to/stored/file.jpg');

        $result = $this->storageService->store($file, $assetId, $version);

        $this->assertEquals('path/to/stored/file.jpg', $result);
    }

    /** @test */
    public function it_deletes_an_asset_directory()
    {
        $assetId = 1;
        $assetPath = 'uploads/0/1';

        $this->pathServiceMock->expects($this->once())
            ->method('getAssetPath')
            ->with($assetId)
            ->willReturn($assetPath);

        Storage::shouldReceive('disk')->andReturnSelf();
        Storage::shouldReceive('deleteDirectory')
            ->once()
            ->with($assetPath)
            ->andReturn(true);

        $result = $this->storageService->deleteDirectory($assetId);

        $this->assertTrue($result);
    }

    /** @test */
    public function it_deletes_an_asset_version_directory()
    {
        $assetId = 1;
        $version = 1;
        $assetVersionPath = 'uploads/0/1/1';

        $this->pathServiceMock->expects($this->once())
            ->method('getAssetVersionPath')
            ->with($assetId, $version)
            ->willReturn($assetVersionPath);

        Storage::shouldReceive('disk')->andReturnSelf();
        Storage::shouldReceive('deleteDirectory')
            ->once()
            ->with($assetVersionPath)
            ->andReturn(true);

        $result = $this->storageService->deleteVersion($assetId, $version);

        $this->assertTrue($result);
    }

    /** @test */
    public function it_returns_transcoded_path_if_exists()
    {
        $assetId = 1;
        $version = 1;
        $versionPath = 'uploads/0/1/1';
        $transcodedPath = $versionPath.'/'.PathService::DEFAULT_FILENAME.'.mp4';
        $defaultFilePath = $versionPath.'/'.PathService::DEFAULT_FILENAME;

        $this->pathServiceMock->expects($this->once())
            ->method('getAssetVersionPath')
            ->with($assetId, $version)
            ->willReturn($versionPath);

        Storage::shouldReceive('disk')->andReturnSelf();
        Storage::shouldReceive('exists')
            ->once()
            ->with($transcodedPath)
            ->andReturn(true);

        $this->pathServiceMock->expects($this->never())
            ->method('getAssetVersionFilePath');

        $result = $this->storageService->getAssetVersionFilePath($assetId, $version);

        $this->assertEquals($transcodedPath, $result);
    }

    /** @test */
    public function it_returns_default_file_path_if_transcoded_does_not_exist()
    {
        $assetId = 1;
        $version = 1;
        $versionPath = 'uploads/0/1/1';
        $transcodedPath = $versionPath.'/'.PathService::DEFAULT_FILENAME.'.mp4';
        $defaultFilePath = $versionPath.'/'.PathService::DEFAULT_FILENAME;

        $this->pathServiceMock->expects($this->once())
            ->method('getAssetVersionPath')
            ->with($assetId, $version)
            ->willReturn($versionPath);

        Storage::shouldReceive('disk')->andReturnSelf();
        Storage::shouldReceive('exists')
            ->once()
            ->with($transcodedPath)
            ->andReturn(false);

        $this->pathServiceMock->expects($this->once())
            ->method('getAssetVersionFilePath')
            ->with($assetId, $version, PathService::DEFAULT_FILENAME)
            ->willReturn($defaultFilePath);

        $result = $this->storageService->getAssetVersionFilePath($assetId, $version);

        $this->assertEquals($defaultFilePath, $result);
    }
}
