<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ZipCreationException;
use App\Models\Asset;
use App\Models\Collection;
use App\Models\Download;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class DownloadService
{
    public function record(Authenticatable $user, Model $downloadable): Download
    {
        return $downloadable->downloads()->create([
            'user_id' => $user->getAuthIdentifier(),
        ]);
    }

    public function getFor(Model $downloadable): \Illuminate\Database\Eloquent\Collection
    {
        return $downloadable->downloads()->with('user')->get();
    }

    public function createBulkDownloadZip(array $assetIds = [], array $collectionIds = []): string
    {
        $assetsToZip = $this->collectAssets($assetIds, $collectionIds);

        if (empty($assetsToZip)) {
            throw new \Exception('No assets found for the given IDs or collections.');
        }

        return $this->createZipFile($assetsToZip);
    }

    private function collectAssets(array $assetIds, array $collectionIds): array
    {
        $assetsToZip = [];

        $directAssets = Asset::whereIn('id', $assetIds)->with('latestVersion')->get();
        foreach ($directAssets as $asset) {
            if ($asset->latestVersion) {
                $fileName = $asset->latestVersion->name.'.'.$asset->latestVersion->extension;
                $assetsToZip[$fileName] = Storage::disk('assets')->path($asset->latestVersion->file_path);
            }
        }

        foreach ($collectionIds as $collectionId) {
            $collection = Collection::with('assets.latestVersion', 'children')->find($collectionId);
            if ($collection) {
                $this->getAssetsForCollections($collection, $assetsToZip, $collection->name);
            }
        }

        return $assetsToZip;
    }

    private function createZipFile(array $assetsToZip): string
    {
        $zipFileName = 'bulk_download_'.now()->format('YmdHis').'.zip';
        $tempDisk = Storage::disk('temp');
        $zipFilePath = $tempDisk->path($zipFileName);

        $zip = new ZipArchive;
        if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new ZipCreationException('Cannot create zip file: '.$zipFilePath);
        }

        $addedFiles = [];

        foreach ($assetsToZip as $zipPath => $actualFilePath) {
            $this->addFileToZip($zip, $zipPath, $actualFilePath, $addedFiles);
        }

        $zip->close();

        return $zipFilePath;
    }

    private function addFileToZip(ZipArchive $zip, string $zipPath, string $actualFilePath, array &$addedFiles): void
    {
        if (file_exists($actualFilePath)) {
            $finalZipPath = $this->getUniqueZipPath($zipPath, $addedFiles);
            $zip->addFile($actualFilePath, $finalZipPath);
            $addedFiles[] = $finalZipPath;
        } else {
            \Log::warning("File not found for path: {$actualFilePath}");
        }
    }

    private function getUniqueZipPath(string $zipPath, array $addedFiles): string
    {
        $finalZipPath = $zipPath;
        $counter = 1;
        while (in_array($finalZipPath, $addedFiles)) {
            $pathInfo = pathinfo($zipPath);
            $finalZipPath = $pathInfo['dirname'].'/'.$pathInfo['filename'].'_'.$counter.'.'.$pathInfo['extension'];
            $counter++;
        }

        return $finalZipPath;
    }

    private function getAssetsForCollections(\App\Models\Collection $collection, array &$assetsToZip, string $currentPath = '')
    {
        // Add assets directly in this collection
        foreach ($collection->assets as $asset) {
            if ($asset->latestVersion) {
                $fileName = $asset->latestVersion->name.'.'.$asset->latestVersion->extension;
                $fullZipPath = trim($currentPath.'/'.$fileName, '/');
                $assetsToZip[$fullZipPath] = Storage::disk('assets')->path($asset->latestVersion->file_path);
            }
        }

        // Recursively add assets from child collections
        foreach ($collection->children as $childCollection) {
            $newPath = trim($currentPath.'/'.$childCollection->name, '/');
            $this->getAssetsForCollections($childCollection, $assetsToZip, $newPath);
        }
    }
}
