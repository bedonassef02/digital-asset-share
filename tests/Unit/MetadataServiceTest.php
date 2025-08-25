<?php

namespace Tests\Unit;

use App\Models\Asset;
use App\Models\AssetVersion;
use App\Services\MediaService;
use App\Services\MetadataService;
use App\Services\StorageService;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class MockMediaServiceForMetadataTest extends MediaService
{
    public function __construct()
    {
        parent::__construct();
    }

    public function setProperties(UploadedFile $file, array &$metadata): void
    {
        // Simulate the behavior of the real MediaService
        // In this test, we just need to ensure mime_type is added
        $metadata['mime_type'] = 'image/jpeg';
    }
}

class MetadataServiceTest extends TestCase
{
    use RefreshDatabase;

    protected MetadataService $metadataService;

    protected $mediaServiceMock;

    protected $storageServiceMock;

    protected $mockDisk;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mediaServiceMock = new MockMediaServiceForMetadataTest;
        $this->storageServiceMock = $this->createMock(StorageService::class);

        // Mock the Storage facade and its disk method
        $this->mockDisk = Mockery::mock(FilesystemAdapter::class);
        $this->mockDisk->shouldReceive('put')->andReturn(true); // Default return for put
        Storage::shouldReceive('disk')->andReturn($this->mockDisk);

        $this->metadataService = new MetadataService($this->mediaServiceMock, $this->storageServiceMock);
    }

    /** @test */
    public function it_invokes_correctly_to_process_and_store_metadata()
    {
        $file = UploadedFile::fake()->image('test.jpg');
        $assetVersion = AssetVersion::factory()->create();

        $expectedHash = hash_file('sha256', $file->getRealPath());

        // The mediaServiceMock (which is MockMediaServiceForMetadataTest) will directly modify the metadata
        // No need for expects()->method() calls on it here.

        $expectedMetadataForStore = [
            'hash' => $expectedHash,
            'mime_type' => 'image/jpeg',
        ];

        // Mock the store method to prevent actual file system interaction during __invoke test
        $metadataService = $this->getMockBuilder(MetadataService::class)
            ->setConstructorArgs([$this->mediaServiceMock, $this->storageServiceMock])
            ->onlyMethods(['store'])
            ->getMock();

        $metadataService->expects($this->once())
            ->method('store')
            ->with($assetVersion, $expectedMetadataForStore);

        ($metadataService)($file, $assetVersion);
    }

    /** @test */
    public function it_stores_metadata_to_the_correct_path()
    {
        $assetVersion = AssetVersion::factory()->create();
        $metadata = ['key' => 'value', 'another' => 'data'];
        $expectedPath = "assets/{$assetVersion->asset_id}/{$assetVersion->version}/metadata.json";

        $this->storageServiceMock->expects($this->once())
            ->method('getAssetVersionFilePath')
            ->with($assetVersion->asset_id, $assetVersion->version, 'metadata.json')
            ->willReturn($expectedPath);

        // The mockDisk is already set up in setUp to return true for put
        // No need to mock Storage::disk() or Storage::put() here again

        $result = $this->metadataService->store($assetVersion, $metadata);

        $this->assertEquals(true, $result);
    }

    /** @test */
    public function it_updates_existing_metadata_file()
    {
        $assetVersion = AssetVersion::factory()->create();
        $originalMetadata = ['name' => 'old_name', 'size' => 123];
        $updatedData = ['name' => 'new_name', 'color' => 'blue'];
        $expectedMergedMetadata = ['name' => 'new_name', 'size' => 123, 'color' => 'blue'];
        $expectedPath = "assets/{$assetVersion->asset_id}/{$assetVersion->version}/metadata.json";

        $this->storageServiceMock->expects($this->once())
            ->method('getAssetVersionFilePath')
            ->with($assetVersion->asset_id, $assetVersion->version, 'metadata.json')
            ->willReturn($expectedPath);

        $this->mockDisk->shouldReceive('exists')
            ->once()
            ->with($expectedPath)
            ->andReturn(true);

        $this->mockDisk->shouldReceive('get')
            ->once()
            ->with($expectedPath)
            ->andReturn(json_encode($originalMetadata));

        // Mock the store method to prevent actual file system interaction during update test
        $this->metadataService = $this->getMockBuilder(MetadataService::class)
            ->setConstructorArgs([$this->mediaServiceMock, $this->storageServiceMock])
            ->onlyMethods(['store'])
            ->getMock();

        $this->metadataService->expects($this->once())
            ->method('store')
            ->with($assetVersion, $expectedMergedMetadata);

        $this->metadataService->update($assetVersion, $updatedData);
    }

    /** @test */
    public function it_does_not_update_metadata_if_file_does_not_exist()
    {
        $assetVersion = AssetVersion::factory()->create();
        $updatedData = ['name' => 'new_name'];
        $expectedPath = "assets/{$assetVersion->asset_id}/{$assetVersion->version}/metadata.json";

        $this->storageServiceMock->expects($this->once())
            ->method('getAssetVersionFilePath')
            ->with($assetVersion->asset_id, $assetVersion->version, 'metadata.json')
            ->willReturn($expectedPath);

        $this->mockDisk->shouldReceive('exists')
            ->once()
            ->with($expectedPath)
            ->andReturn(false);

        $this->mockDisk->shouldNotReceive('get');

        // Ensure store method is not called
        $this->metadataService = $this->getMockBuilder(MetadataService::class)
            ->setConstructorArgs([$this->mediaServiceMock, $this->storageServiceMock])
            ->onlyMethods(['store'])
            ->getMock();

        $this->metadataService->expects($this->never())
            ->method('store');

        $this->metadataService->update($assetVersion, $updatedData);
    }

    /** @test */
    public function it_gets_metadata_when_latest_version_and_file_exist()
    {
        $asset = Asset::factory()->create();
        $assetVersion = AssetVersion::factory()->create(['asset_id' => $asset->id]);
        $asset->update(['latest_version_id' => $assetVersion->id]);

        $metadata = ['key' => 'value'];
        $expectedPath = "assets/{$asset->id}/{$assetVersion->version}/metadata.json";

        $this->storageServiceMock->expects($this->once())
            ->method('getAssetVersionFilePath')
            ->with($asset->id, $assetVersion->version, 'metadata.json')
            ->willReturn($expectedPath);

        $this->mockDisk->shouldReceive('exists')
            ->once()
            ->with($expectedPath)
            ->andReturn(true);

        $this->mockDisk->shouldReceive('get')
            ->once()
            ->with($expectedPath)
            ->andReturn(json_encode($metadata));

        $result = $this->metadataService->get($asset);

        $this->assertEquals($metadata, $result);
    }

    /** @test */
    public function it_returns_empty_array_when_latest_version_does_not_exist()
    {
        $asset = Asset::factory()->create(['latest_version_id' => null]);

        $this->storageServiceMock->expects($this->never())
            ->method('getAssetVersionFilePath');

        $this->mockDisk->shouldNotReceive('exists');
        $this->mockDisk->shouldNotReceive('get');

        $result = $this->metadataService->get($asset);

        $this->assertEquals([], $result);
    }

    /** @test */
    public function it_returns_empty_array_when_metadata_file_does_not_exist()
    {
        $asset = Asset::factory()->create();
        $assetVersion = AssetVersion::factory()->create(['asset_id' => $asset->id]);
        $asset->update(['latest_version_id' => $assetVersion->id]);

        $expectedPath = "assets/{$asset->id}/{$assetVersion->version}/metadata.json";

        $this->storageServiceMock->expects($this->once())
            ->method('getAssetVersionFilePath')
            ->with($asset->id, $assetVersion->version, 'metadata.json')
            ->willReturn($expectedPath);

        $this->mockDisk->shouldReceive('exists')
            ->once()
            ->with($expectedPath)
            ->andReturn(false);

        $this->mockDisk->shouldNotReceive('get');

        $result = $this->metadataService->get($asset);

        $this->assertEquals([], $result);
    }
}
