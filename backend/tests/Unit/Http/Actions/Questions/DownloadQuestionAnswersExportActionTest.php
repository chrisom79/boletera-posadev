<?php

namespace Tests\Unit\Http\Actions\Questions;

use HiEvents\Http\Actions\Questions\DownloadQuestionAnswersExportAction;
use HiEvents\Http\ResponseCodes;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class DownloadQuestionAnswersExportActionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('filesystems.private', 'local');
        Storage::fake('local');
    }

    public function testDownloadsFileWhenItExists(): void
    {
        Storage::disk('local')->put('event_1/answers-job-uuid.xlsx', 'fake-xlsx-contents');

        $response = (new DownloadQuestionAnswersExportAction())(1, 'job-uuid');

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testReturnsNotFoundWhenFileIsMissing(): void
    {
        $response = (new DownloadQuestionAnswersExportAction())(1, 'job-uuid');

        $this->assertSame(ResponseCodes::HTTP_NOT_FOUND, $response->getStatusCode());
    }
}
