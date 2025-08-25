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
use App\Services\FileHashService;

class AssetServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AssetService $assetService;
    protected $storageServiceMock;
    protected $metadataServiceMock;
    protected $pathServiceMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->storageServiceMock = $this->createMock(StorageService::class);
        $this->metadataServiceMock = $this->createMock(MetadataService::class);
        $this->fileHashServiceMock = $this->createMock(\App\Services\FileHashService::class);
        $this->pathServiceMock = $this->createMock(\App\Services\PathService::class);

        // Bind the mocked PathService to the container
        $this->app->instance(PathService::class, $this->pathServiceMock);

        $this->assetService = new AssetService($this->storageServiceMock, $this->metadataServiceMock, $this->fileHashServiceMock);
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

        // Mock PathService and Storage facade for ImageThumbnail
        $mockFilePath = 'uploads/1/1/file'; // Example path
        $this->pathServiceMock->expects($this->any())
                               ->method('getAssetVersionFilePath')
                               ->willReturn($mockFilePath);

        Storage::shouldReceive('disk')->andReturnSelf();
        Storage::shouldReceive('get')
               ->with($mockFilePath)
               ->andReturn('dummy image content'); // Provide dummy content for the image

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
        $asset->update(['latest_version_id' => $assetVersion->id]);

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
        $asset->update(['latest_version_id' => $assetVersion->id]);

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

    /** @test */
    public function it_creates_a_new_asset_and_links_to_existing_version_if_file_hash_exists()
    {
        $user = User::factory()->create();
        $existingAsset = Asset::factory()->create(['user_id' => $user->id]);
        $existingVersion = AssetVersion::factory()->create([
            'asset_id' => $existingAsset->id,
            'file_hash' => 'existing_hash',
            'version' => 1
        ]);
        $existingAsset->update(['latest_version_id' => $existingVersion->id]);

        $file = UploadedFile::fake()->image('duplicate.jpg');
        $data = ['user_id' => $user->id];

        $this->fileHashServiceMock->expects($this->once())
                                 ->method('calculateFileHash')
                                 ->with($file)
                                 ->willReturn('existing_hash');

        // Expect no calls to store or metadata service for the new asset
        $this->storageServiceMock->expects($this->never())
                                 ->method('store');
        $this->metadataServiceMock->expects($this->never())
                                  ->method('__invoke');

        $newAsset = $this->assetService->create($file, $data);
        $newAsset = Asset::find($newAsset->id);

        $this->assertInstanceOf(Asset::class, $newAsset);
        $this->assertNotNull($newAsset->id);
        $this->assertNotEquals($existingAsset->id, $newAsset->id);
        $this->assertEquals($existingVersion->id, $newAsset->latest_version_id);
        $this->assertEquals($existingVersion->id, $newAsset->latest_version_id);
        $this->assertNotNull($newAsset->latestVersion);
        $this->assertEquals($existingVersion->id, $newAsset->latestVersion->id);

        $this->assertDatabaseHas('assets', [
            'id' => $newAsset->id,
            'user_id' => $user->id,
            'latest_version_id' => $existingVersion->id,
        ]);
        // Ensure no new asset version was created
        $this->assertDatabaseMissing('asset_versions', [
            'asset_id' => $newAsset->id,
            'file_hash' => 'existing_hash',
            'version' => 1,
        ]);
    }

    /** @test */
    public function it_creates_a_new_version_and_links_to_existing_version_if_file_hash_exists()
    {
        $user = User::factory()->create();
        $asset = Asset::factory()->create(['user_id' => $user->id]);
        $existingVersion = AssetVersion::factory()->create([
            'asset_id' => $asset->id,
            'file_hash' => 'existing_hash',
            'version' => 1
        ]);
        $asset->update(['latest_version_id' => $existingVersion->id]);

        $file = UploadedFile::fake()->image('duplicate_new_version.jpg');
        $data = [];

        $this->fileHashServiceMock->expects($this->once())
                                 ->method('calculateFileHash')
                                 ->with($file)
                                 ->willReturn('existing_hash');

        // Expect no calls to store or metadata service for the new version
        $this->storageServiceMock->expects($this->never())
                                 ->method('store');
        $this->metadataServiceMock->expects($this->never())
                                  ->method('__invoke');

        $newVersion = $this->assetService->createNewVersion($asset->id, $file, $data);

        $this->assertInstanceOf(AssetVersion::class, $newVersion);
        $this->assertEquals($existingVersion->id, $newVersion->id);
        $this->assertEquals($existingVersion->id, $asset->fresh()->latest_version_id);

        // Ensure no new asset version was created
        $this->assertDatabaseCount('asset_versions', 1);
    }

    /** @test */
    public function it_restores_a_soft_deleted_asset()
    {
        $user = User::factory()->create();
        $asset = Asset::factory()->create(['user_id' => $user->id]);
        $asset->delete(); // Soft delete the asset

        $this->assertSoftDeleted('assets', ['id' => $asset->id]);

        $this->assetService->restore($asset->id);

        $this->assertNotSoftDeleted('assets', ['id' => $asset->id]);
        $this->assertEquals(Asset::STATUS_ACTIVE, $asset->fresh()->status);
    }

    /** @test */
    public function it_changes_an_asset_status()
    {
        $user = User::factory()->create();
        $asset = Asset::factory()->create(['user_id' => $user->id, 'status' => Asset::STATUS_ACTIVE]);

        $this->assertEquals(Asset::STATUS_ACTIVE, $asset->status);

        $this->assetService->changeStatus($asset->id, Asset::STATUS_ARCHIVED);

        $this->assertEquals(Asset::STATUS_ARCHIVED, $asset->fresh()->status);
    }

    /** @test */
    public function it_bulk_soft_deletes_assets()
    {
        $user = User::factory()->create();
        $assets = Asset::factory()->count(3)->create(['user_id' => $user->id]);
        $assetIds = $assets->pluck('id')->toArray();

        $this->assetService->bulkSoftDelete($assetIds);

        foreach ($assetIds as $assetId) {
            $this->assertSoftDeleted('assets', ['id' => $assetId]);
        }
    }

    /** @test */
    public function it_dispatches_jobs_for_image_asset_creation()
    {
        Queue::fake();

        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('test.jpg');
        $data = ['user_id' => $user->id];

        $this->fileHashServiceMock->expects($this->once())
                                 ->method('calculateFileHash')
                                 ->willReturn('unique_hash');

        $this->assetService->create($file, $data);

        Queue::assertPushed(GenerateThumbnail::class);
        Queue::assertNotPushed(TranscodeVideo::class);
    }

    /** @test */
    public function it_dispatches_jobs_for_video_asset_creation()
    {
        Queue::fake();

        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('video.mp4', 100, 'video/mp4');
        $data = ['user_id' => $user->id];

        $this->fileHashServiceMock->expects($this->once())
                                 ->method('calculateFileHash')
                                 ->willReturn('unique_hash');

        $this->assetService->create($file, $data);

        Queue::assertPushed(GenerateThumbnail::class);
        Queue::assertPushed(TranscodeVideo::class);
    }

    /** @test */
    public function it_retrieves_all_assets_including_trashed_for_a_user()
    {
        $user = User::factory()->create();
        Asset::factory()->count(2)->create(['user_id' => $user->id]);
        Asset::factory()->count(1)->trashed()->create(['user_id' => $user->id]);
        Asset::factory()->count(1)->create(); // Another user's asset

        $assets = $this->assetService->findAll($user->id, 15, true, false);

        $this->assertCount(3, $assets);
        $this->assertTrue($assets->every(fn ($asset) => $asset->user_id === $user->id));
    }

    /** @test */
    public function it_retrieves_only_trashed_assets_for_a_user()
    {
        $user = User::factory()->create();
        Asset::factory()->count(2)->create(['user_id' => $user->id]);
        Asset::factory()->count(1)->trashed()->create(['user_id' => $user->id]);
        Asset::factory()->count(1)->create(); // Another user's asset

        $assets = $this->assetService->findAll($user->id, 15, false, true);

        $this->assertCount(1, $assets);
        $this->assertTrue($assets->every(fn ($asset) => $asset->user_id === $user->id && $asset->trashed()));
    }
}