<?php

namespace App\Services;

use App\Models\Asset;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportService
{
    public function __invoke(Asset $asset, string $format)
    {
        $data = [
            'id' => $asset->id,
            'name' => $asset->name,
            'description' => $asset->description,
            'mime_type' => $asset->mime_type,
            'size' => $asset->size,
            'extension' => $asset->extension,
            'user_id' => $asset->user_id,
            'created_at' => $asset->created_at->toIso8601String(),
            'updated_at' => $asset->updated_at->toIso8601String(),
            'metadata' => $asset->metadata, // Assuming metadata is already an array/object
            'tags' => $asset->tags->pluck('name')->toArray(),
            // Add any other stats or related data here
        ];

        if ($format === 'json') {
            return response()->json($data);
        } elseif ($format === 'csv') {
            return $this->exportCsv([$data]);
        } else {
            abort(400, 'Invalid export format. Supported formats are JSON and CSV.');
        }
    }

    protected function exportCsv(array $data): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="asset_export.csv"',
        ];

        $callback = function () use ($data) {
            $file = fopen('php://output', 'w');

            if (empty($data)) {
                fputcsv($file, ['No data available']);
                fclose($file);
                return;
            }

            // Get headers from the first item, assuming all items have the same keys
            $headerRow = array_keys($data[0]);
            fputcsv($file, $headerRow);

            foreach ($data as $row) {
                // Flatten nested arrays/objects for CSV
                $flatRow = [];
                foreach ($row as $key => $value) {
                    if (is_array($value) || is_object($value)) {
                        $flatRow[$key] = json_encode($value); // Convert nested structures to JSON string
                    } else {
                        $flatRow[$key] = $value;
                    }
                }
                fputcsv($file, $flatRow);
            }
            fclose($file);
        };

        return new StreamedResponse($callback, 200, $headers);
    }
}
