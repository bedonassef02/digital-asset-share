<?php

namespace App\Services;

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

    public function getFor(Model $downloadable)
    {
        return $downloadable->downloads()->with('user')->get();
    }

    /**
     * Creates a zip archive of multiple assets and/or assets from collections.
     *
     * @param array $assetIds
     * @param array $collectionIds
     * @return string Path to the created zip file.
     * @throws \Exception
     */
    public function createBulkDownloadZip(array $assetIds = [], array $collectionIds = []): string
    {
        $assetsToZip = []; // This will store ['zip_path' => 'actual_file_path']

        // Add directly specified assets
        $directAssets = Asset::whereIn('id', $assetIds)->with('latestVersion')->get();
        foreach ($directAssets as $asset) {
            if ($asset->latestVersion) {
                $fileName = $asset->latestVersion->name . '.' . $asset->latestVersion->extension;
                $assetsToZip[$fileName] = Storage::disk('assets')->path($asset->latestVersion->file_path);
            }
        }

        // Add assets from collections and their children
        foreach ($collectionIds as $collectionId) {
            $collection = Collection::with('assets.latestVersion', 'children')->find($collectionId);
            if ($collection) {
                $this->getAssetsForCollections($collection, $assetsToZip, $collection->name);
            }
        }

        if (empty($assetsToZip)) {
            throw new \Exception('No assets found for the given IDs or collections.');
        }

        $zipFileName = 'bulk_download_' . now()->format('YmdHis') . '.zip';
        $tempDisk = Storage::disk('temp');
        $zipFilePath = $tempDisk->path($zipFileName);

        $zip = new ZipArchive();
        if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \Exception('Cannot create zip file: ' . $zipFilePath);
        }

        $addedFiles = []; // To track files already added to the zip to handle duplicates

        foreach ($assetsToZip as $zipPath => $actualFilePath) {
            if (file_exists($actualFilePath)) {
                $finalZipPath = $zipPath;
                $counter = 1;
                // Handle duplicate names within the zip
                while (in_array($finalZipPath, $addedFiles)) {
                    $pathInfo = pathinfo($zipPath);
                    $finalZipPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '_' . $counter . '.' . $pathInfo['extension'];
                    $counter++;
                }
                $zip->addFile($actualFilePath, $finalZipPath);
                $addedFiles[] = $finalZipPath;
            } else {
                \Log::warning("File not found for path: {$actualFilePath}");
            }
        }

        $zip->close();

        return $zipFilePath;
    }

    /**
     * Recursively collects assets from a collection and its children.
     *
     * @param \App\Models\Collection $collection
     * @param array $assetsToZip
     * @param string $currentPath
     * @return void
     */
    private function getAssetsForCollections(\App\Models\Collection $collection, array &$assetsToZip, string $currentPath = '')
    {
        // Add assets directly in this collection
        foreach ($collection->assets as $asset) {
            if ($asset->latestVersion) {
                $fileName = $asset->latestVersion->name . '.' . $asset->latestVersion->extension;
                $fullZipPath = trim($currentPath . '/' . $fileName, '/');
                $assetsToZip[$fullZipPath] = Storage::disk('assets')->path($asset->latestVersion->file_path);
            }
        }

        // Recursively add assets from child collections
        foreach ($collection->children as $childCollection) {
            $newPath = trim($currentPath . '/' . $childCollection->name, '/');
            $this->getAssetsForCollections($childCollection, $assetsToZip, $newPath);
        }
    }
}

