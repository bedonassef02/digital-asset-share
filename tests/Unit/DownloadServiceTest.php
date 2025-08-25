<?php

namespace Tests\Unit;

use App\Models\Asset;
use App\Models\Collection;
use App\Models\Download;
use App\Models\User;
use App\Services\DownloadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

class DownloadServiceTest extends TestCase
{
    use RefreshDatabase;

    protected DownloadService $downloadService;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->downloadService = new DownloadService;
        $this->user = User::factory()->create();

        Storage::fake('assets');
        Storage::fake('temp');
    }

    /** @test */
    public function it_records_a_download_for_an_asset()
    {
        $asset = Asset::factory()->create();

        $download = $this->downloadService->record($this->user, $asset);

        $this->assertInstanceOf(Download::class, $download);
        $this->assertEquals($this->user->id, $download->user_id);
        $this->assertEquals($asset->id, $download->downloadable_id);
        $this->assertEquals(Asset::class, $download->downloadable_type);
        $this->assertDatabaseHas('downloads', [
            'user_id' => $this->user->id,
            'downloadable_id' => $asset->id,
            'downloadable_type' => Asset::class,
        ]);
    }

    /** @test */
    public function it_records_a_download_for_a_collection()
    {
        $collection = Collection::factory()->create();

        $download = $this->downloadService->record($this->user, $collection);

        $this->assertInstanceOf(Download::class, $download);
        $this->assertEquals($this->user->id, $download->user_id);
        $this->assertEquals($collection->id, $download->downloadable_id);
        $this->assertEquals(Collection::class, $download->downloadable_type);
        $this->assertDatabaseHas('downloads', [
            'user_id' => $this->user->id,
            'downloadable_id' => $collection->id,
            'downloadable_type' => Collection::class,
        ]);
    }

    /** @test */
    public function it_retrieves_downloads_for_an_asset()
    {
        $asset = Asset::factory()->create();
        Download::factory()->count(3)->create([
            'downloadable_id' => $asset->id,
            'downloadable_type' => Asset::class,
        ]);
        Download::factory()->count(2)->create(); // Other downloads

        $downloads = $this->downloadService->getFor($asset);

        $this->assertCount(3, $downloads);
        $this->assertTrue($downloads->every(fn ($d) => $d->downloadable_type === Asset::class && $d->downloadable_id === $asset->id));
    }

    /** @test */
    public function it_retrieves_downloads_for_a_collection()
    {
        $collection = Collection::factory()->create();
        Download::factory()->count(3)->create([
            'downloadable_id' => $collection->id,
            'downloadable_type' => Collection::class,
        ]);
        Download::factory()->count(2)->create(); // Other downloads

        $downloads = $this->downloadService->getFor($collection);

        $this->assertCount(3, $downloads);
        $this->assertTrue($downloads->every(fn ($d) => $d->downloadable_type === Collection::class && $d->downloadable_id === $collection->id));
    }

    /** @test */
    public function it_creates_a_zip_with_direct_assets()
    {
        $asset1 = Asset::factory()->create();
        $asset2 = Asset::factory()->create();
        $asset1->latestVersion()->create(['asset_id' => $asset1->id, 'version' => 1, 'mime_type' => 'text/plain', 'size' => 100, 'name' => 'file1', 'extension' => 'txt', 'file_path' => 'path/to/file1.txt']);
        $asset2->latestVersion()->create(['asset_id' => $asset2->id, 'version' => 1, 'mime_type' => 'image/jpeg', 'name' => 'file2', 'extension' => 'jpg', 'file_path' => 'path/to/file2.jpg']);

        Storage::disk('assets')->put('path/to/file1.txt', 'content1');
        Storage::disk('assets')->put('path/to/file2.jpg', 'content2');

        // Mock ZipArchive
        $zipMock = $this->getMockBuilder(ZipArchive::class)
            ->onlyMethods(['open', 'addFile', 'close'])
            ->getMock();

        $zipMock->expects($this->once())
            ->method('open')
            ->willReturn(true);

        $zipMock->expects($this->exactly(2))
            ->method('addFile')
            ->withConsecutive(
                [Storage::disk('assets')->path('path/to/file1.txt'), 'file1.txt'],
                [Storage::disk('assets')->path('path/to/file2.jpg'), 'file2.jpg']
            );

        $zipMock->expects($this->once())
            ->method('close')
            ->willReturn(true);

        // Replace the ZipArchive instance with our mock
        $this->app->instance(ZipArchive::class, $zipMock);

        $zipFilePath = $this->downloadService->createBulkDownloadZip([$asset1->id, $asset2->id]);

        $this->assertStringContainsString('bulk_download_', $zipFilePath);
        $this->assertStringContainsString('.zip', $zipFilePath);
        Storage::disk('temp')->assertExists(basename($zipFilePath));
    }

    /** @test */
    public function it_creates_a_zip_with_assets_from_a_single_collection()
    {
        $collection = Collection::factory()->create();
        $asset1 = Asset::factory()->create();
        $asset2 = Asset::factory()->create();
        $asset1->latestVersion()->create(['asset_id' => $asset1->id, 'version' => 1, 'mime_type' => 'text/plain', 'name' => 'col_file1', 'extension' => 'txt', 'file_path' => 'path/to/col_file1.txt']);
        $asset2->latestVersion()->create(['asset_id' => $asset2->id, 'version' => 1, 'mime_type' => 'image/jpeg', 'name' => 'col_file2', 'extension' => 'jpg', 'file_path' => 'path/to/col_file2.jpg']);
        $collection->assets()->attach([$asset1->id, $asset2->id]);

        Storage::disk('assets')->put('path/to/col_file1.txt', 'content1');
        Storage::disk('assets')->put('path/to/col_file2.jpg', 'content2');

        // Mock ZipArchive
        $zipMock = $this->getMockBuilder(ZipArchive::class)
            ->onlyMethods(['open', 'addFile', 'close'])
            ->getMock();

        $zipMock->expects($this->once())
            ->method('open')
            ->willReturn(true);

        $zipMock->expects($this->exactly(2))
            ->method('addFile')
            ->withConsecutive(
                [Storage::disk('assets')->path('path/to/col_file1.txt'), $collection->name.'/col_file1.txt'],
                [Storage::disk('assets')->path('path/to/col_file2.jpg'), $collection->name.'/col_file2.jpg']
            );

        $zipMock->expects($this->once())
            ->method('close')
            ->willReturn(true);

        // Replace the ZipArchive instance with our mock
        $this->app->instance(ZipArchive::class, $zipMock);

        $zipFilePath = $this->downloadService->createBulkDownloadZip([], [$collection->id]);

        $this->assertStringContainsString('bulk_download_', $zipFilePath);
        $this->assertStringContainsString('.zip', $zipFilePath);
        Storage::disk('temp')->assertExists(basename($zipFilePath));
    }

    /** @test */
    public function it_creates_a_zip_with_assets_from_nested_collections()
    {
        $parentCollection = Collection::factory()->create(['name' => 'ParentCol']);
        $childCollection = Collection::factory()->create(['name' => 'ChildCol', 'parent_id' => $parentCollection->id]);

        $asset1 = Asset::factory()->create();
        $asset2 = Asset::factory()->create();
        $asset1->latestVersion()->create(['asset_id' => $asset1->id, 'version' => 1, 'mime_type' => 'text/plain', 'name' => 'nested_file1', 'extension' => 'txt', 'file_path' => 'path/to/nested_file1.txt']);
        $asset2->latestVersion()->create(['asset_id' => $asset2->id, 'version' => 1, 'mime_type' => 'image/jpeg', 'name' => 'nested_file2', 'extension' => 'jpg', 'file_path' => 'path/to/nested_file2.jpg']);

        $parentCollection->assets()->attach([$asset1->id]);
        $childCollection->assets()->attach([$asset2->id]);

        Storage::disk('assets')->put('path/to/nested_file1.txt', 'content1');
        Storage::disk('assets')->put('path/to/nested_file2.jpg', 'content2');

        // Mock ZipArchive
        $zipMock = $this->getMockBuilder(ZipArchive::class)
            ->onlyMethods(['open', 'addFile', 'close'])
            ->getMock();

        $zipMock->expects($this->once())
            ->method('open')
            ->willReturn(true);

        $zipMock->expects($this->exactly(2))
            ->method('addFile')
            ->withConsecutive(
                [Storage::disk('assets')->path('path/to/nested_file1.txt'), 'ParentCol/nested_file1.txt'],
                [Storage::disk('assets')->path('path/to/nested_file2.jpg'), 'ParentCol/ChildCol/nested_file2.jpg']
            );

        $zipMock->expects($this->once())
            ->method('close')
            ->willReturn(true);

        // Replace the ZipArchive instance with our mock
        $this->app->instance(ZipArchive::class, $zipMock);

        $zipFilePath = $this->downloadService->createBulkDownloadZip([], [$parentCollection->id]);

        $this->assertStringContainsString('bulk_download_', $zipFilePath);
        $this->assertStringContainsString('.zip', $zipFilePath);
        Storage::disk('temp')->assertExists(basename($zipFilePath));
    }

    /** @test */
    public function it_throws_exception_if_no_assets_found_for_bulk_download()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No assets found for the given IDs or collections.');

        $this->downloadService->createBulkDownloadZip([], []);
    }

    /** @test */
    public function it_handles_duplicate_filenames_in_zip()
    {
        $asset1 = Asset::factory()->create();
        $asset2 = Asset::factory()->create();
        $asset1->latestVersion()->create(['asset_id' => $asset1->id, 'version' => 1, 'mime_type' => 'text/plain', 'name' => 'duplicate_file', 'extension' => 'txt', 'file_path' => 'path/to/duplicate_file1.txt']);
        $asset2->latestVersion()->create(['asset_id' => $asset2->id, 'version' => 1, 'mime_type' => 'text/plain', 'name' => 'duplicate_file', 'extension' => 'txt', 'file_path' => 'path/to/duplicate_file2.txt']);

        Storage::disk('assets')->put('path/to/duplicate_file1.txt', 'content1');
        Storage::disk('assets')->put('path/to/duplicate_file2.txt', 'content2');

        // Mock ZipArchive
        $zipMock = $this->getMockBuilder(ZipArchive::class)
            ->onlyMethods(['open', 'addFile', 'close'])
            ->getMock();

        $zipMock->expects($this->once())
            ->method('open')
            ->willReturn(true);

        $zipMock->expects($this->exactly(2))
            ->method('addFile')
            ->withConsecutive(
                [Storage::disk('assets')->path('path/to/duplicate_file1.txt'), 'duplicate_file.txt'],
                [Storage::disk('assets')->path('path/to/duplicate_file2.txt'), 'duplicate_file_1.txt'] // Expect renamed
            );

        $zipMock->expects($this->once())
            ->method('close')
            ->willReturn(true);

        // Replace the ZipArchive instance with our mock
        $this->app->instance(ZipArchive::class, $zipMock);

        $zipFilePath = $this->downloadService->createBulkDownloadZip([$asset1->id, $asset2->id]);

        $this->assertStringContainsString('bulk_download_', $zipFilePath);
        $this->assertStringContainsString('.zip', $zipFilePath);
        Storage::disk('temp')->assertExists(basename($zipFilePath));
    }

    /** @test */
    public function it_creates_a_zip_with_mixed_direct_assets_and_collections()
    {
        $directAsset = Asset::factory()->create();
        $directAsset->latestVersion()->create(['asset_id' => $directAsset->id, 'version' => 1, 'mime_type' => 'text/plain', 'name' => 'direct_file', 'extension' => 'txt', 'file_path' => 'path/to/direct_file.txt']);
        Storage::disk('assets')->put('path/to/direct_file.txt', 'content_direct');

        $collection = Collection::factory()->create(['name' => 'MixedCol']);
        $collectionAsset = Asset::factory()->create();
        $collectionAsset->latestVersion()->create(['asset_id' => $collectionAsset->id, 'version' => 1, 'mime_type' => 'image/jpeg', 'name' => 'collection_file', 'extension' => 'jpg', 'file_path' => 'path/to/collection_file.jpg']);
        $collection->assets()->attach([$collectionAsset->id]);
        Storage::disk('assets')->put('path/to/collection_file.jpg', 'content_collection');

        // Mock ZipArchive
        $zipMock = $this->getMockBuilder(ZipArchive::class)
            ->onlyMethods(['open', 'addFile', 'close'])
            ->getMock();

        $zipMock->expects($this->once())
            ->method('open')
            ->willReturn(true);

        $zipMock->expects($this->exactly(2))
            ->method('addFile')
            ->withConsecutive(
                [Storage::disk('assets')->path('path/to/direct_file.txt'), 'direct_file.txt'],
                [Storage::disk('assets')->path('path/to/collection_file.jpg'), 'MixedCol/collection_file.jpg']
            );

        $zipMock->expects($this->once())
            ->method('close')
            ->willReturn(true);

        // Replace the ZipArchive instance with our mock
        $this->app->instance(ZipArchive::class, $zipMock);

        $zipFilePath = $this->downloadService->createBulkDownloadZip([$directAsset->id], [$collection->id]);

        $this->assertStringContainsString('bulk_download_', $zipFilePath);
        $this->assertStringContainsString('.zip', $zipFilePath);
        Storage::disk('temp')->assertExists(basename($zipFilePath));
    }
}
