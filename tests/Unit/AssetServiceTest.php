<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Asset;
use App\Models\AssetVersion;
use App\Services\AssetService;
use App\Services\StorageService;
use App\Services\MetadataService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Queue;
use App\Jobs\GenerateThumbnail;
use App\Jobs\TranscodeVideo;
use App\Jobs\ReplaceAssetTags;

class AssetServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AssetService $assetService;
    protected $storageServiceMock;
    protected $metadataServiceMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->storageServiceMock = $this->createMock(StorageService::class);
        $this->metadataServiceMock = $this->createMock(MetadataService::class);
        $this->assetService = new AssetService($this->storageServiceMock, $this->metadataServiceMock);
        Storage::fake('local'); // Mock the storage disk
    }

    /** @test */
    public function it_creates_a_new_asset_and_version()
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('test.jpg');
        $data = [
            'user_id' => $user->id,
            'name' => 'Test Image',
            'description' => 'A test image for unit testing.',
        ];

        // Expect calls to mocked services
        $this->storageServiceMock->expects($this->once())
                                 ->method('store')
                                 ->with($file, $this->anything(), 1); // Asset ID will be dynamic

        $this->metadataServiceMock->expects($this->once())
                                  ->method('__invoke')
                                  ->with($file, $this->isInstanceOf(AssetVersion::class));

        $asset = $this->assetService->create($file, $data);

        $this->assertInstanceOf(Asset::class, $asset);
        $this->assertNotNull($asset->id);
        $this->assertNotNull($asset->latestVersion);
        $this->assertEquals('Test Image', $asset->latestVersion->name);
        $this->assertEquals('A test image for unit testing.', $asset->latestVersion->description);
        $this->assertEquals($file->getMimeType(), $asset->latestVersion->mime_type);
        $this->assertEquals($file->getSize(), $asset->latestVersion->size);
        $this->assertEquals($file->extension(), $asset->latestVersion->extension);

        // Assert database has the records
        $this->assertDatabaseHas('assets', [
            'id' => $asset->id,
            'user_id' => $user->id,
        ]);
        $this->assertDatabaseHas('asset_versions', [
            'asset_id' => $asset->id,
            'name' => 'Test Image',
            'description' => 'A test image for unit testing.',
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'extension' => $file->extension(),
        ]);
    }

    /** @test */
    public function it_finds_an_asset_by_id_and_user_id()
    {
        $user = User::factory()->create();
        $asset = Asset::factory()->create(['user_id' => $user->id]);

        $foundAsset = $this->assetService->findOne($asset->id, $user->id);

        $this->assertInstanceOf(Asset::class, $foundAsset);
        $this->assertEquals($asset->id, $foundAsset->id);
    }

    /** @test */
    public function it_throws_exception_if_asset_not_found_for_user()
    {
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();
        $asset = Asset::factory()->create(['user_id' => $anotherUser->id]);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $this->assetService->findOne($asset->id, $user->id);
    }

    /** @test */
    public function it_retrieves_all_assets_for_a_user()
    {
        $user = User::factory()->create();
        Asset::factory()->count(3)->create(['user_id' => $user->id]);
        Asset::factory()->count(2)->create(); // Assets for another user

        $assets = $this->assetService->findAll($user->id);

        $this->assertCount(3, $assets);
        $this->assertTrue($assets->every(fn ($asset) => $asset->user_id === $user->id));
    }

    /** @test */
    public function it_updates_an_asset_version_details()
    {
        \Illuminate\Support\Facades\Queue::fake();

        $user = User::factory()->create();
        $asset = Asset::factory()->create(['user_id' => $user->id]);
        $assetVersion = AssetVersion::factory()->create(['asset_id' => $asset->id]);

        $updatedData = [
            'name' => 'Updated Name',
            'description' => 'Updated Description',
            'tags' => ['tag1', 'tag2'],
        ];

        $updatedAsset = $this->assetService->update($asset->id, $updatedData);

        $this->assertInstanceOf(Asset::class, $updatedAsset);
        $this->assertEquals('Updated Name', $updatedAsset->latestVersion->name);
        $this->assertEquals('Updated Description', $updatedAsset->latestVersion->description);
        $this->assertDatabaseHas('asset_versions', [
            'id' => $assetVersion->id,
            'name' => 'Updated Name',
            'description' => 'Updated Description',
        ]);

        Queue::assertPushed(ReplaceAssetTags::class, function ($job) use ($asset) {
            return $job->assetId === $asset->id && $job->tags === ['tag1', 'tag2'];
        });
    }

    /** @test */
    public function it_updates_an_asset_version_details_without_tags()
    {
        \Illuminate\Support\Facades\Queue::fake();

        $user = User::factory()->create();
        $asset = Asset::factory()->create(['user_id' => $user->id]);
        $assetVersion = AssetVersion::factory()->create(['asset_id' => $asset->id]);

        $updatedData = [
            'name' => 'Updated Name Only',
        ];

        $updatedAsset = $this->assetService->update($asset->id, $updatedData);

        $this->assertInstanceOf(Asset::class, $updatedAsset);
        $this->assertEquals('Updated Name Only', $updatedAsset->latestVersion->name);
        $this->assertDatabaseHas('asset_versions', [
            'id' => $assetVersion->id,
            'name' => 'Updated Name Only',
        ]);

        \Illuminate\Support\Facades\Queue::assertNotPushed(ReplaceAssetTags::class);
    }

    /** @test */
    public function it_soft_deletes_an_asset()
    {
        $user = User::factory()->create();
        $asset = Asset::factory()->create(['user_id' => $user->id]);

        $this->assetService->softDelete($asset->id);

        $this->assertSoftDeleted('assets', ['id' => $asset->id]);
    }

    /** @test */
    public function it_force_deletes_an_asset()
    {
        $user = User::factory()->create();
        $asset = Asset::factory()->create(['user_id' => $user->id]);

        // Soft delete first to ensure it's in the trash
        $this->assetService->softDelete($asset->id);

        $this->storageServiceMock->expects($this->once())
                                 ->method('deleteDirectory')
                                 ->with($asset->id);

        $this->assetService->forceDelete($asset->id);

        $this->assertDatabaseMissing('assets', ['id' => $asset->id]);
    }

    /** @test */
    public function it_creates_a_new_version_for_an_existing_asset()
    {
        $user = User::factory()->create();
        $asset = Asset::factory()->create(['user_id' => $user->id]);
        AssetVersion::factory()->create(['asset_id' => $asset->id, 'version' => 1]);

        $file = UploadedFile::fake()->image('new_version.png');
        $data = [
            'name' => 'New Version Image',
            'description' => 'Description for new version.',
        ];

        // Expect calls to mocked services and jobs
        $this->storageServiceMock->expects($this->once())
                                 ->method('store')
                                 ->with($file, $asset->id, 2); // Expect version 2

        $this->metadataServiceMock->expects($this->once())
                                  ->method('__invoke')
                                  ->with($file, $this->isInstanceOf(AssetVersion::class));

        Queue::fake();

        $newVersion = $this->assetService->createNewVersion($asset->id, $file, $data);

        $this->assertInstanceOf(AssetVersion::class, $newVersion);
        $this->assertEquals($asset->id, $newVersion->asset_id);
        $this->assertEquals(2, $newVersion->version);
        $this->assertEquals('New Version Image', $newVersion->name);
        $this->assertEquals('Description for new version.', $newVersion->description);
        $this->assertEquals($file->getMimeType(), $newVersion->mime_type);
        $this->assertEquals($file->getSize(), $newVersion->size);
        $this->assertEquals($file->extension(), $newVersion->extension);

        // Assert asset's latest_version_id is updated
        $asset->refresh();
        $this->assertEquals($newVersion->id, $asset->latest_version_id);

        // Assert jobs dispatched
        Queue::assertPushed(GenerateThumbnail::class, function ($job) use ($newVersion) {
            return $job->assetVersion->id === $newVersion->id;
        });
        Queue::assertNotPushed(TranscodeVideo::class);

        $this->assertDatabaseHas('asset_versions', [
            'asset_id' => $asset->id,
            'version' => 2,
            'name' => 'New Version Image',
        ]);
    }

    /** @test */
    public function it_dispatches_transcode_video_job_for_video_new_version()
    {
        $user = User::factory()->create();
        $asset = Asset::factory()->create(['user_id' => $user->id]);
        AssetVersion::factory()->create(['asset_id' => $asset->id, 'version' => 1]);

        $file = UploadedFile::fake()->create('video.mp4', 100, 'video/mp4');
        $data = ['name' => 'Video Asset'];

        Queue::fake();

        $this->assetService->createNewVersion($asset->id, $file, $data);

        Queue::assertPushed(GenerateThumbnail::class);
        Queue::assertPushed(TranscodeVideo::class);
    }

    /** @test */
    public function it_handles_asset_not_found_when_creating_new_version()
    {
        $file = UploadedFile::fake()->image('test.jpg');
        $data = ['name' => 'Test Image'];

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $this->assetService->createNewVersion(999, $file, $data);
    }
}