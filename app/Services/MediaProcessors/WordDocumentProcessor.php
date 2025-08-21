<?php

namespace App\Services\MediaProcessors;

use Illuminate\Http\UploadedFile;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use Illuminate\Support\Facades\Log;

class WordDocumentProcessor implements MediaProcessorInterface
{
    public function canProcess(UploadedFile $file): bool
    {
        // Check for common DOCX MIME types
        $mimeType = $file->getMimeType();
        return in_array($mimeType, [
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/zip', // Sometimes DOCX files are identified as zip
        ]);
    }

    public function process(UploadedFile $file, array &$metadata): void
    {
        try {
            $phpWord = IOFactory::load($file->getRealPath());

            // Extract document properties
            $properties = $phpWord->getDocInfo();

            $metadata['title'] = $properties->getTitle();
            $metadata['author'] = $properties->getCreator();
            $metadata['subject'] = $properties->getSubject();
            $metadata['keywords'] = $properties->getKeywords();
            $metadata['description'] = $properties->getDescription();
            $metadata['category'] = $properties->getCategory();
            $metadata['last_modified_by'] = $properties->getLastModifiedBy();
            $metadata['created_at'] = $properties->getCreated();
            $metadata['modified_at'] = $properties->getModified();

            // Attempt to get page count (PhpWord doesn't directly provide this easily)
            // This often requires rendering or more complex parsing, so we'll leave it out for now
            // or add a placeholder if needed.
            $metadata['word_count'] = $this->getWordCount($phpWord);

        } catch (\Exception $e) {
            Log::error('Failed to process DOCX file: ' . $e->getMessage());
        }
    }

    private function getWordCount(PhpWord $phpWord): int
    {
        $wordCount = 0;
        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                if ($element instanceof \PhpOffice\PhpWord\Element\TextRun) {
                    foreach ($element->getElements() as $textElement) {
                        if ($textElement instanceof \PhpOffice\PhpWord\Element\Text) {
                            $wordCount += str_word_count($textElement->getText());
                        }
                    }
                } elseif ($element instanceof \PhpOffice\PhpWord\Element\Text) {
                    $wordCount += str_word_count($element->getText());
                }
            }
        }
        return $wordCount;
    }
}
