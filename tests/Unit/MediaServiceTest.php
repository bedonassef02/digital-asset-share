<?php

namespace Tests\Unit;

use App\Services\MediaProcessors\MediaProcessorInterface;
use App\Services\MediaService;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class MediaServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
    public function it_calls_the_correct_processor_for_a_given_file()
    {
        $mockProcessor = $this->createMock(MediaProcessorInterface::class);
        $mockProcessor->method('canProcess')->willReturn(true);
        $mockProcessor->expects($this->once())
            ->method('process');

        $mediaService = new MediaService($mockProcessor);
        $file = UploadedFile::fake()->image('test.jpg');
        $metadata = [];

        $mediaService->setProperties($file, $metadata);
    }

    /** @test */
    public function it_does_not_call_any_processor_if_none_can_process_the_file()
    {
        $mockProcessor = $this->createMock(MediaProcessorInterface::class);
        $mockProcessor->method('canProcess')->willReturn(false);
        $mockProcessor->expects($this->never())
            ->method('process');

        $mediaService = new MediaService($mockProcessor);
        $file = UploadedFile::fake()->image('test.jpg');
        $metadata = [];

        $mediaService->setProperties($file, $metadata);
    }

    /** @test */
    public function it_only_calls_the_first_matching_processor()
    {
        $mockProcessor1 = $this->createMock(MediaProcessorInterface::class);
        $mockProcessor1->method('canProcess')->willReturn(true);
        $mockProcessor1->expects($this->once())
            ->method('process');

        $mockProcessor2 = $this->createMock(MediaProcessorInterface::class);
        $mockProcessor2->method('canProcess')->willReturn(true);
        $mockProcessor2->expects($this->never())
            ->method('process');

        $mediaService = new MediaService($mockProcessor1, $mockProcessor2);
        $file = UploadedFile::fake()->image('test.jpg');
        $metadata = [];

        $mediaService->setProperties($file, $metadata);
    }
}
