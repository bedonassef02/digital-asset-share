<?php

namespace App\Services\MediaProcessors;

use Illuminate\Http\UploadedFile;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use Illuminate\Support\Facades\Log;

class WordDocumentProcessor extends AbstractOfficeProcessor
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

    protected function getDocumentProperties(UploadedFile $file)
    {
        return IOFactory::load($file->getRealPath())->getDocInfo();
    }

    public function process(UploadedFile $file, array &$metadata): void
    {
        parent::process($file, $metadata); // Call parent to extract common properties

        try {
            $phpWord = IOFactory::load($file->getRealPath());
            $metadata['word_count'] = $this->getWordCount($phpWord);

        } catch (\Exception $e) {
            Log::error('Failed to process DOCX specific properties: ' . $e->getMessage());
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