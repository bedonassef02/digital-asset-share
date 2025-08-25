<?php

namespace Tests\Unit;

use App\Services\PathService;
use Tests\TestCase;

class PathServiceTest extends TestCase
{
    protected PathService $pathService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pathService = new PathService;
    }

    /** @test */
    public function it_returns_the_correct_shard_directory()
    {
        $this->assertEquals('uploads/0', $this->pathService->getShardDirectory(1));
        $this->assertEquals('uploads/0', $this->pathService->getShardDirectory(999));
        $this->assertEquals('uploads/0', $this->pathService->getShardDirectory(1000));
        $this->assertEquals('uploads/1', $this->pathService->getShardDirectory(1001));
        $this->assertEquals('uploads/1', $this->pathService->getShardDirectory(2000));
    }

    /** @test */
    public function it_returns_the_correct_asset_path()
    {
        $this->assertEquals('uploads/0/1', $this->pathService->getAssetPath(1));
        $this->assertEquals('uploads/0/1000', $this->pathService->getAssetPath(1000));
        $this->assertEquals('uploads/1/1001', $this->pathService->getAssetPath(1001));
    }

    /** @test */
    public function it_returns_the_correct_asset_version_path()
    {
        $this->assertEquals('uploads/0/1/1', $this->pathService->getAssetVersionPath(1, 1));
        $this->assertEquals('uploads/0/1000/5', $this->pathService->getAssetVersionPath(1000, 5));
    }

    /** @test */
    public function it_returns_the_correct_asset_version_file_path()
    {
        $this->assertEquals('uploads/0/1/1/image.jpg', $this->pathService->getAssetVersionFilePath(1, 1, 'image.jpg'));
        $this->assertEquals('uploads/0/1000/5/document.pdf', $this->pathService->getAssetVersionFilePath(1000, 5, 'document.pdf'));
    }

    /** @test */
    public function it_returns_the_correct_asset_version_file_path_with_default_filename()
    {
        $this->assertEquals('uploads/0/1/1/file', $this->pathService->getAssetVersionFilePath(1, 1));
        $this->assertEquals('uploads/0/1000/5/file', $this->pathService->getAssetVersionFilePath(1000, 5));
    }
}
