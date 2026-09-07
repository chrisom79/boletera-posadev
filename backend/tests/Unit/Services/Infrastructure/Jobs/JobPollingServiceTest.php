<?php

namespace Tests\Unit\Services\Infrastructure\Jobs;

use HiEvents\Services\Infrastructure\Jobs\Enum\JobStatusEnum;
use HiEvents\Services\Infrastructure\Jobs\JobPollingService;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Mockery as m;
use Tests\TestCase;

class JobPollingServiceTest extends TestCase
{
    private JobPollingService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('filesystems.private', 'local');
        Storage::fake('local');

        $this->service = new JobPollingService();
    }

    public function testReturnsNotFoundWhenBatchDoesNotExist(): void
    {
        Bus::shouldReceive('findBatch')->once()->with('missing-uuid')->andReturn(null);

        $result = $this->service->checkJobStatus('missing-uuid');

        $this->assertSame(JobStatusEnum::NOT_FOUND, $result->status);
    }

    public function testReturnsInProgressWhenBatchNotFinished(): void
    {
        $batch = m::mock(Batch::class);
        $batch->shouldReceive('finished')->andReturn(false);
        Bus::shouldReceive('findBatch')->once()->andReturn($batch);

        $result = $this->service->checkJobStatus('job-uuid');

        $this->assertSame(JobStatusEnum::IN_PROGRESS, $result->status);
    }

    public function testReturnsNotFoundWhenFinishedButFileMissing(): void
    {
        $batch = m::mock(Batch::class);
        $batch->shouldReceive('finished')->andReturn(true);
        Bus::shouldReceive('findBatch')->once()->andReturn($batch);

        $result = $this->service->checkJobStatus('job-uuid', 'event_1/answers-job-uuid.xlsx');

        $this->assertSame(JobStatusEnum::NOT_FOUND, $result->status);
        $this->assertEquals(__('Export file not found'), $result->message);
    }

    public function testReturnsFinishedWithSignedDownloadUrlWhenFileExists(): void
    {
        Storage::disk('local')->put('event_1/answers-job-uuid.xlsx', 'fake-xlsx-contents');

        $batch = m::mock(Batch::class);
        $batch->shouldReceive('finished')->andReturn(true);
        Bus::shouldReceive('findBatch')->once()->andReturn($batch);

        $result = $this->service->checkJobStatus(
            jobUuid: 'job-uuid',
            filePath: 'event_1/answers-job-uuid.xlsx',
            downloadRoute: 'questions.answers.export.download',
            downloadRouteParams: ['eventId' => 1, 'jobUuid' => 'job-uuid'],
        );

        $this->assertSame(JobStatusEnum::FINISHED, $result->status);
        $this->assertNotNull($result->downloadUrl);
        $this->assertStringContainsString('/events/1/questions/answers/export/download/job-uuid', $result->downloadUrl);
        $this->assertStringContainsString('signature=', $result->downloadUrl);
    }

    public function testFinishedWithNoFilePathHasNoDownloadUrl(): void
    {
        $batch = m::mock(Batch::class);
        $batch->shouldReceive('finished')->andReturn(true);
        Bus::shouldReceive('findBatch')->once()->andReturn($batch);

        $result = $this->service->checkJobStatus('job-uuid');

        $this->assertSame(JobStatusEnum::FINISHED, $result->status);
        $this->assertNull($result->downloadUrl);
    }

    protected function tearDown(): void
    {
        m::close();
        parent::tearDown();
    }
}
